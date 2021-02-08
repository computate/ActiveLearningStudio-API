<?php

namespace App\Http\Controllers\Api\V1\CurrikiGo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Activity\ActivityRepositoryInterface;
use App\Repositories\LRSStatementsData\LRSStatementsDataRepositoryInterface;
use App\Services\LearnerRecordStoreService;
use Illuminate\Support\Facades\DB;
use App\CurrikiGo\LRS\InteractionFactory;

/**
 * @group 16. XAPI
 *
 * Cron job for XAPI extract
 */
class ExtractXAPIJSONController extends Controller
{
    /**
     * Runs the xAPI extract job script
     *
     * @param  ActivityRepositoryInterface  $activityRepository
     * @param  LRSStatementsDataRepositoryInterface  $lrsStatementsRepository
     * @return void
     */
    public function runJob(ActivityRepositoryInterface $activityRepository, LRSStatementsDataRepositoryInterface $lrsStatementsRepository)
    {
        $max_statement_id = $lrsStatementsRepository->findMaxByField('statement_id');
        if (!$max_statement_id) {
            $max_statement_id = 0;
        }
        \Log::info(date('Y-m-d h:i:s') . ' - Extract XAPI script - started from max ID: '. $max_statement_id);
        $offset = 0;
        $limit = config('xapi.lrs_job_row_limit');
        $xapiStatements = DB::connection('lrs_pgsql')->table(config('xapi.lrs_db_statements_table'))->select()
                ->offset($offset)
                ->limit($limit)
                ->where('voided', false)
                ->where('id', '>', $max_statement_id)
                ->orderby('id', 'ASC')
                ->get();

        try {
            $service = new LearnerRecordStoreService();
            foreach ($xapiStatements as $row) {
                \Log::info(date('Y-m-d h:i:s') . ' - Processing XAPI statement with id: ' . $row->id);
                $insertData = [];
                $statement = $service->buildStatementfromJSON($row->data);
                $actor = $statement->getActor();
                $target = $statement->getTarget();
                
                $verb = $service->getVerbFromStatement($statement->getVerb());
                $context = $statement->getContext();
                $nameOfActivity = 'Unknown Quiz set';
                $defaultActivityName = true;
                
                // At the moment, we're only tackling targets of 'activity' type.
                $definition = ($target->getObjectType() === 'Activity' ? $target->getDefinition() : '');
               
                // In some cases, we do not have a 'name' property for the object.
                // So, we've added an additional check here.
                // @todo - the LRS statements generated need to have this property
                if ($target->getObjectType() === 'Activity' && !empty($definition) && !$definition->getName()->isEmpty()) {
                    $nameOfActivity = $definition->getName()->getNegotiatedLanguageString();
                    $defaultActivityName = false;
                } elseif ($target->getObjectType() === 'StatementRef') {
                    $nameOfActivity = $target->getId();
                }
                
                $result = $statement->getResult();

                if (!empty($actor->getAccount())) {
                    $insertData['actor_id'] = $actor->getAccount()->getName();
                    $insertData['actor_homepage'] = $actor->getAccount()->getHomePage();
                } else {
                    // If will be mbox / name
                    $insertData['actor_id'] = $actor->getMbox();
                    $insertData['actor_homepage'] = $actor->getName();
                }
                
                $insertData['statement_id'] = $row->id;
                $insertData['statement_uuid'] = $statement->getId();
                
                $insertData['object_name'] = $nameOfActivity;
                $insertData['datetime'] = $row->created_at;
                $insertData['object_id'] = $target->getId();
                $insertData['verb'] = $verb;

                if (!empty($context)) {
                    $contextActivities = $context->getContextActivities();
                    $other = $contextActivities->getOther();
                    $groupingInfo = $service->findGroupingInfo($other);
                    $platform = $context->getPlatform();
                }

                // Skip if we don't have the activity.
                if (empty($groupingInfo['activity']) || empty($groupingInfo['class']) || empty($context)) {
                    // It maybe an old format statement. Just save verb, object and actor, and move on.
                    $inserted = $lrsStatementsRepository->create($insertData);
                    if ($inserted) {
                        \Log::info(date('Y-m-d h:i:s') . ' - XAPI statement with id: ' . $row->id . ' processed');
                    }
                    continue;
                }
                // REMOVE THIS
                $groupingInfo['activity'] = 18045;
                $activity = $activityRepository->find($groupingInfo['activity']);
                $activityId = null;
                $activityName = null;
                $projectId = null;
                $projectName = null;
                $playlistId = null;
                $playlistTitle = null;

                if ($activity) {
                    $activityId = $activity->id;
                    $activityName = $activity->title;
                    $project = $activity->playlist->project;
                    $projectId = $project->id;
                    $projectName = $project->name;
                    $playlistId = $activity->playlist_id;
                    $playlistTitle = $activity->playlist->title;
                }
                
                $category = $contextActivities->getCategory();
                $categoryId = '';
                $h5pInteraction = '';
                if (!empty($category)) {
                    $categoryId = end($category)->getId();
                    $h5pInteraction = explode("/", $categoryId);
                    $h5pInteraction = end($h5pInteraction);
                }

                $insertData['class_id'] = $groupingInfo['class'];
                $insertData['project_id'] = $projectId;
                $insertData['playlist_id'] = $playlistId;
                $insertData['assignment_submitted'] = ($verb === LearnerRecordStoreService::SUBMITTED_VERB_NAME ? TRUE : FALSE);
                
                $insertData['activity_category'] = $h5pInteraction;
                $insertData['platform'] = $platform;
                $insertData['project_name'] = $projectName;
                
                $insertData['playlist_name'] = $playlistTitle;
                $insertData['assignment_id'] = $activityId;
                $insertData['assignment_name'] = $activityName;

                $interactionFactory = new InteractionFactory();
                $interaction = $interactionFactory->initInteraction($statement);
                if ($interaction) {
                    $interactionSummary = $interaction->summary();
                    // Pull this from interaction...
                    $insertData['question'] = $interactionSummary['description'];
                    $insertData['duration'] = (!empty($interactionSummary['raw-duration']) ? $interactionSummary['raw-duration'] : null);
                    $insertData['options'] = (isset($interactionSummary['choices']) ? implode(", ", $interactionSummary['choices']) : null);
                    if (isset($interactionSummary['response']) && !empty($interactionSummary['response'])) { 
                        $insertData['answer'] = (is_array($interactionSummary['response']) ? implode(", ", $interactionSummary['response']) : $interactionSummary['response']);
                    }
                    if ($interactionSummary['scorable'] || (isset($interactionSummary['score']) && $interactionSummary['score']['max'] > 0)) {
                        $insertData['score_scaled'] = $interactionSummary['score']['scaled'];
                        $insertData['score_min'] = $interactionSummary['score']['min'];
                        $insertData['score_max'] = $interactionSummary['score']['max'];
                        $insertData['score_raw'] = $interactionSummary['score']['raw'];
                    }
                }
                // Overriding object name, when we have Questionnaire H5P, and object name is not available.
                if ($defaultActivityName && ($h5pInteraction 
                && in_array($insertData['verb'], ['completed', 'progressed']) && preg_match('/^H5P.Questionnaire/', $h5pInteraction, $matches))) {
                    $insertData['object_name'] = $matches[0];
                }
                // need to determine column layout interaction on 'completed'.
                if (($h5pInteraction 
                && in_array($insertData['verb'], ['completed', 'progressed']) && preg_match('/^H5P.Column/', $h5pInteraction))) {
                    $insertData['page'] = $insertData['object_name'];
                    $insertData['page_completed'] = $insertData['verb'] === 'completed' ? true : false;
                }
                
                $inserted = $lrsStatementsRepository->create($insertData);
                if ($inserted) {
                    \Log::info(date('Y-m-d h:i:s') . ' - XAPI statement with id: ' . $row->id . ' processed');
                }
            }
            \Log::info(date('Y-m-d h:i:s') . ' - Extract XAPI script ended');
        } catch (Exception $e) {
            \Log::error($e->getMessage());
        }
    }
}
