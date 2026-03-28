<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductAttribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Product')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('General')->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('slug')->maxLength(255),
                        Forms\Components\TextInput::make('sku')->label('SKU')->maxLength(255),
                        Forms\Components\Select::make('brand_id')->relationship('brand', 'name')->searchable()->preload()->nullable(),
                        Forms\Components\Select::make('categories')->relationship('categories', 'name')->multiple()->searchable()->preload(),
                        Forms\Components\RichEditor::make('short_description'),
                        Forms\Components\RichEditor::make('description'),
                    ]),
                    Forms\Components\Tabs\Tab::make('Pricing')->schema([
                        Forms\Components\TextInput::make('price')->numeric()->prefix('Tk'),
                        Forms\Components\TextInput::make('compare_price')->numeric()->prefix('Tk'),
                        Forms\Components\TextInput::make('cost_price')->numeric()->prefix('Tk'),
                        Forms\Components\Toggle::make('in_stock')->default(true),
                        Forms\Components\TextInput::make('stock_quantity')->numeric()->default(0)->label('Stock Quantity'),
                        Forms\Components\TextInput::make('min_stock_quantity')->numeric()->default(5)->label('Min Stock (Low Stock Alert)')->helperText('Alert shows when stock falls at or below this number'),
                    ]),
                    Forms\Components\Tabs\Tab::make('Media')->schema([
                        SpatieMediaLibraryFileUpload::make('product_thumbnail')->collection('product_thumbnail')->image(),
                        SpatieMediaLibraryFileUpload::make('product_images')->collection('product_images')->multiple()->reorderable()->image(),
                    ]),
                    Forms\Components\Tabs\Tab::make('SEO')->schema([
                        Forms\Components\TextInput::make('meta_title')->maxLength(255),
                        Forms\Components\Textarea::make('meta_description')->rows(3),
                    ]),
                    Forms\Components\Tabs\Tab::make('Attributes')->schema([
                        Forms\Components\Repeater::make('attributeValues')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_attribute_id')
                                    ->label('Attribute')
                                    ->options(ProductAttribute::orderBy('sort_order')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\TextInput::make('value')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Attribute Value')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                ($state['product_attribute_id'] ?? null)
                                    ? (ProductAttribute::find($state['product_attribute_id'])?->name . ': ' . ($state['value'] ?? ''))
                                    : null
                            ),
                    ]),
                    Forms\Components\Tabs\Tab::make('Details')->schema([
                        Forms\Components\KeyValue::make('specifications'),
                        Forms\Components\TextInput::make('weight')->maxLength(255),
                        Forms\Components\TextInput::make('dimensions')->maxLength(255),
                        Forms\Components\TextInput::make('warranty_info')->maxLength(255),
                    ]),
                ])->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\Toggle::make('is_featured')->default(false),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            SpatieMediaLibraryImageColumn::make('product_thumbnail')->collection('product_thumbnail')->circular(),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable(),
            Tables\Columns\TextColumn::make('price')->money('BDT')->sortable(),
            Tables\Columns\TextColumn::make('brand.name')->sortable(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\IconColumn::make('is_featured')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Tables\Filters\TernaryFilter::make('is_active'),
            Tables\Filters\TernaryFilter::make('is_featured'),
            Tables\Filters\SelectFilter::make('brand')->relationship('brand', 'name'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
