<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Story;
use App\Models\Users;
use App\Models\Bookmark;
use App\Policies\StoryPolicy;
use App\Policies\UsersPolicy;
use App\Policies\BookmarkPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Story::class => StoryPolicy::class,
        Users::class => UsersPolicy::class,
        Bookmark::class => BookmarkPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
