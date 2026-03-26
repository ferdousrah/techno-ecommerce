<?php

namespace App\Filament\Widgets;

use App\Models\BlogPost;
use App\Models\ContactSubmission;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Products', Product::count())
                ->description('All products in catalog')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),
            Stat::make('Total Orders', ContactSubmission::count())
                ->description('Contact submissions received')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('warning'),
            Stat::make('Blog Posts', BlogPost::count())
                ->description('Published articles')
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('info'),
            Stat::make('Newsletter Subscribers', NewsletterSubscriber::count())
                ->description('Active subscribers')
                ->descriptionIcon('heroicon-m-at-symbol')
                ->color('danger'),
        ];
    }
}
