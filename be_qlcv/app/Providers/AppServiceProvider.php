<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            \App\Repositories\User\UserInterface::class,
            \App\Repositories\User\UserRepository::class,
        );
        $this->app->singleton(
            \App\Repositories\Authentication\AuthInterface::class,
            \App\Repositories\Authentication\AuthRepository::class
        );
        $this->app->singleton(
            \App\Repositories\Project\ProjectInterface::class,
            \App\Repositories\Project\ProjectRepository::class
        );
        $this->app->singleton(
            \App\Repositories\Member\MemberInterface::class,
            \App\Repositories\Member\MemberRepository::class
        );

        $this->app->singleton(
            \App\Repositories\Comment\CommentInterface::class,
            \App\Repositories\Comment\CommentRepository::class
        );

        $this->app->singleton(
            \App\Repositories\Task\TaskInterface::class,
            \App\Repositories\Task\TaskRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
