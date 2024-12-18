<?php

namespace App\Providers;

use App\Repositories\ContentRepository;
use App\Repositories\EpisodeRepository;
use App\Repositories\GenreRepository;
use App\Repositories\Interfaces\ContentRepositoryInterface;
use App\Repositories\Interfaces\EpisodeRepositoryInterface;
use App\Repositories\Interfaces\GenreRepositoryInterface;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GenreRepositoryInterface::class, GenreRepository::class);
        $this->app->bind(ContentRepositoryInterface::class,ContentRepository::class);
        $this->app->bind(EpisodeRepositoryInterface::class, EpisodeRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
