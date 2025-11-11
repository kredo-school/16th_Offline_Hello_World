<?php
namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Access\Response;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    // /**
    //  * The policy mappings for the application.
    //  *
    //  * @var array<class-string, class-string>
    //  */
    protected $policies = [];

    // /**
    //  * Register the application's policies.
    //  *
    //  * @return void
    //  */
    // public function register()
    // {
    //     $this->booting(function () {
    //         $this->registerPolicies();
    //     });
    // }

    // /**
    //  * Register the application's policies.
    //  *
    //  * @return void
    //  */
    // public function registerPolicies()
    // {
    //     foreach ($this->policies() as $model => $policy) {
    //         Gate::policy($model, $policy);
    //     }
    // }

    // /**
    //  * Get the policies defined on the provider.
    //  *
    //  * @return array<class-string, class-string>
    //  */
    // public function policies()
    // {
    //     return $this->policies;
    // }

    public function boot()
    {
        $this->registerPolicies();

        // Role mapping: 1=admin, 2=teacher, 3=student, 4=basic_user
        Gate::define('admin', function (User $user) {
            return $user->role_id == 1
                ? Response::allow()
                : Response::deny('Administrator privileges are required.');
        });

        Gate::define('teachers', function (User $user) {
            return $user->role_id == 2
                ? Response::allow()
                : Response::deny('This page is for teachers.');
        });

        Gate::define('students', function (User $user) {
            return $user->role_id == 3
                ? Response::allow()
                : Response::deny('This page is for students.');
        });

        Gate::define('basic_user', function (User $user) {
            return $user->role_id == 4
                ? Response::allow()
                : Response::deny('This page is for basic user.');
        });

        Gate::define('view-student-history', function (User $user, User $student) {
        // 本人
        if ($user->id === $student->id) {
            return true;
        }

        // Admin
        if ((int) $user->role_id === 1) {
            return true;
        }

        // Teacher（全教師に見せて良いならこれでOK）
        if ((int) $user->role_id === 2) {
            return true;
        }

        return false;
    });

    Paginator::useBootstrapFive(); // Bootstrap5の場合
    }
}