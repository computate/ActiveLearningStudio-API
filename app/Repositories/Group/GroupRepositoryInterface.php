<?php

namespace App\Repositories\Group;

use App\Repositories\EloquentRepositoryInterface;
use App\Models\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface GroupRepositoryInterface extends EloquentRepositoryInterface
{

    /**
     * Create pivots data on group creation
     *
     * @param $group
     * @param $data
     */
    public function createGroup($group, $data);

    /**
     * Invite user to the group
     *
     * @param $group
     * @param $user
     */
    public function inviteToGroup($group, $user);

    /**
     * Invite members to the group
     *
     * @param $group
     * @param $data
     * @return bool
     */
    public function inviteMembers($group, $data);

    /**
     * Set Group / Project / User relationship
     *
     * @param $group
     * @param $projects
     * @param $users
     */
    public function setGroupProjectUser($group, $projects, $users);

    /**
     * Remove Group / Project / User relationship
     *
     * @param $group
     * @param $user
     */
    public function removeGroupProjectUser($group, $user);

    /**
     * Remove Group / User / Project relationship
     *
     * @param $group
     * @param $project
     */
    public function removeGroupUserProject($group, $project);

    /**
     * Assign members to the group project
     *
     * @param $group
     * @param $project
     * @param $users
     */
    public function assignMembersToGroupProject($group, $project, $users);

    /**
     * Remove member from the group project
     *
     * @param $group
     * @param $project
     * @param $user
     */
    public function removeMemberFromGroupProject($group, $project, $user);

    /**
     * Get Group detail data
     *
     * @param $groupId
     * @return mixed
     */
    public function getGroupDetail($groupId);

}
