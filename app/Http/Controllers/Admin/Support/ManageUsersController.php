<?php

namespace App\Http\Controllers\Admin\Support;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\Users\UserEditService;
use App\Utilities\SortBy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManageUsersController extends Controller
{
    public $indexColumns = [

        'users.id',
        'users.name',
        'users.email',
        'users.is_admin',
        'users.is_active',
        'users.is_subscriber',
        'users.is_influencer',

    ];

    public function index(Request $request)
    {

        $columns = $this->indexColumns;

        [$sortBy, $sortOrder] = SortBy::setSortBy($request, $columns);

        $users = User::select($columns)

            ->orderBy($sortBy, $sortOrder)

            ->paginate(10);

        $section_heading = 'Manage Users';
        $edit_endpoint = 'manage-user/edit/';
        $details_endpoint = 'manage-user/';
        $delete_endpoint = 'delete-user/';
        $search_endpoint = 'manage-users/search/';

        return response()->json([

            'status' => 'success',
            'section_heading' => $section_heading,
            'edit_endpoint' => $edit_endpoint,
            'details_endpoint' => $details_endpoint,
            'delete_endpoint' => $delete_endpoint,
            'search_endpoint' => $search_endpoint,
            'users' => $users,

        ], 200);

    }

    public function show(int $id)
    {

        $columns = [

            'users.id',
            'users.name',
            'users.email',
            'users.email_verified_at',
            'users.is_admin',
            'users.is_active',
            'users.is_subscriber',
            'users.is_influencer',
            'users.stripe_id',
            'users.pm_type',
            'users.pm_last_four',
            'users.created_at',
            'users.updated_at',

        ];

        $user = User::select($columns)

            ->where('users.id', $id)

            ->first();

        return response()->json([

            'status' => 'success',
            'user' => $user,

        ], 200);
    }

    public function editFormConfig(int $id, UserEditService $service)
    {

        return $service->getFormConfigs($id);
    }

    public function update(Request $request, int $id)
    {

        $request->validate([

            'name' => 'required|string',
            'email' => [
                'required',
                'string',
                'max:140',

                Rule::unique('users')->ignore($id),
            ],

            'is_admin' => 'boolean|required',

        ]);

        $user = User::find($id);

        $user->update($request->all());

        return response()->json([

            'status' => 'success',
            'message' => 'user updated',
            'user' => $user,

        ], 201);

    }

    public function destroy(int $id)
    {

        // use sparingly

        User::destroy($id);

        return response()->json([

            'status' => 'success',
            'message' => 'The user has been deleted.',
        ], 200);

    }

    public function search(Request $request, string $keyword)
    {

        $columns = $this->indexColumns;

        [$sortBy, $sortOrder] = SortBy::setSortBy($request, $columns);

        $users = User::select($columns)

            ->where('users.name', 'like', '%'.$keyword.'%')

            ->orWhere('users.email', 'like', '%'.$keyword.'%')

            ->orderBy($sortBy, $sortOrder)

            ->paginate(10);

        $section_heading = 'Manage Users';
        $edit_endpoint = 'manage-user/edit/';
        $details_endpoint = 'manage-user/';
        $delete_endpoint = 'delete-user/';

        return response()->json([

            'status' => 'success',
            'section_heading' => $section_heading,
            'edit_endpoint' => $edit_endpoint,
            'details_endpoint' => $details_endpoint,
            'delete_endpoint' => $delete_endpoint,
            'users' => $users,

        ], 200);

    }
}
