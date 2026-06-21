<tr>
    @include('admin.partials.user-sortable-th', ['column' => 'name', 'label' => 'Name', 'sort' => $sort, 'direction' => $direction])
    @include('admin.partials.user-sortable-th', ['column' => 'email', 'label' => 'Email', 'sort' => $sort, 'direction' => $direction])
    @if($hasTeamLeaderColumn)
        <th scope="col" class="admin-prod-th">Team leader</th>
    @endif
    @include('admin.partials.user-sortable-th', ['column' => 'role', 'label' => 'Role', 'sort' => $sort, 'direction' => $direction])
    <th scope="col" class="admin-prod-th">Region</th>
    <th scope="col" class="admin-prod-th">Branch</th>
    @include('admin.partials.user-sortable-th', ['column' => 'status', 'label' => 'Status', 'sort' => $sort, 'direction' => $direction])
    @include('admin.partials.user-sortable-th', ['column' => 'created_at', 'label' => 'Joined', 'sort' => $sort, 'direction' => $direction])
    <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
</tr>
