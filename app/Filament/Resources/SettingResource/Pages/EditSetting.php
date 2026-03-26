<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // When type is image, copy the uploaded file path to value
        if (($data['type'] ?? '') === 'image' && isset($data['image_upload'])) {
            $data['value'] = $data['image_upload'];
        }
        unset($data['image_upload']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Clear the setting cache
        Cache::forget("setting.{$this->record->key}");
    }
}
