<?php

/**
 * This file contains handlers for searching LMS courses.
 */

namespace App\Http\Controllers\Api\V1\CurrikiGo;

use App\CurrikiGo\Canvas\Client;
use App\CurrikiGo\Canvas\Course as CanvasCourse;
use App\CurrikiGo\Moodle\Course as MoodleCourse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CurrikiGo\FetchCourseRequest;
use App\Models\Project;
use App\Repositories\CurrikiGo\LmsSetting\LmsSettingRepository;
use App\Repositories\CurrikiGo\LmsSetting\LmsSettingRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * @group  CurrikiGo
 * @authenticated
 *
 * APIs for fetching courses from LMSs
 */
class CourseController extends Controller
{
    /**
     * $lmsSettingRepository
     */
    private $lmsSettingRepository;

    /**
     * CourseController constructor.
     *
     * @param LmsSettingRepositoryInterface $lmsSettingRepository
     */
    public function __construct(LmsSettingRepositoryInterface $lmsSettingRepository)
    {
        $this->lmsSettingRepository = $lmsSettingRepository;
    }

    /**
     * Fetch a course from Canvas
     *
     * @urlParam  project required The ID of the project.
     * @bodyParam  setting_id int The id of the LMS setting.
     *
     * @responseFile  responses/fetchfromcanvas.post.json
     * @response  400 {
     *  "errors": "Validation error"
     * }
     * @response  403 {
     *  "errors": "You are not authorized to perform this action."
     * }
     *
     * @param Project $project
     * @param FetchCourseRequest $fetchRequest
     * @return Response
     */
    public function fetchFromCanvas(Project $project, FetchCourseRequest $fetchRequest)
    {
        $authUser = auth()->user();
        if (Gate::forUser($authUser)->allows('fetch-lms-course', $project)) {
            $data = $fetchRequest->validated();
            $lmsSettings = $this->lmsSettingRepository->find($data['setting_id']);
            $canvasClient = new Client($lmsSettings);
            $canvasCourse = new CanvasCourse($canvasClient);
            $outcome = $canvasCourse->fetch($project);

            return response([
                'project' => $outcome,
            ], 200);
        }

        return response([
            'errors' => ['You are not authorized to perform this action.'],
        ], 403);
    }

    /**
     * Fetch a course from Moodle
     *
     * @urlParam  project required The ID of the project.
     * @bodyParam  setting_id int The id of the LMS setting.
     *
     * @responseFile  responses/fetchfromcanvas.post.json
     * @response  400 {
     *  "errors": "Validation error"
     * }
     * @response  403 {
     *  "errors": "You are not authorized to perform this action."
     * }
     *
     * @param FetchCourseRequest $fetchRequest
     * @param Project $project
     * @return Response
     */
    public function fetchFromMoodle(FetchCourseRequest $fetchRequest, Project $project)
    {
        $authUser = auth()->user();
        if (Gate::forUser($authUser)->allows('fetch-lms-course', $project)) {
            $data = $fetchRequest->validated();
            $lmsSetting = $this->lmsSettingRepository->find($data['setting_id']);
            $moodleCourse = new MoodleCourse($lmsSetting);
            $response = $moodleCourse->fetch($project);
            $outcome = $response->getBody()->getContents();
            return response([
                'project' => json_decode($outcome),
            ], 200);
        }

        return response([
            'errors' => ['You are not authorized to perform this action.'],
        ], 403);
    }
}
