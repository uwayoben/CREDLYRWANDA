<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\RawHtmlContent;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Company')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Company Details')
                                    ->description('Enter the primary information about the company')
                                    ->icon('heroicon-o-information-circle')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Company Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-building-office')
                                                    ->placeholder('e.g., ABC Ltd')
                                                    ->columnSpan(2),

                                                TextInput::make('registration_number')
                                                    ->label('Registration Number')
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-document-text')
                                                    ->placeholder('e.g., RDB/2024/001')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('email')
                                                    ->label('Email Address')
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-envelope')
                                                    ->placeholder('info@company.com')
                                                    ->unique(ignoreRecord: true),

                                                TextInput::make('phone')
                                                    ->label('Phone Number')
                                                    ->tel()
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->placeholder('+250 788 123 456'),
                                            ]),

                                        TextInput::make('address')
                                            ->label('Physical Address')
                                            ->required()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->placeholder('Street, Building, Landmark')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Contact Person')
                                    ->description('Information about the primary contact')
                                    ->icon('heroicon-o-user')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('contact_name')
                                                    ->label('Contact Person Name')
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->placeholder('John Doe'),

                                                TextInput::make('contact_phone')
                                                    ->label('Contact Phone')
                                                    ->tel()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->placeholder('+250 788 123 456'),

                                                TextInput::make('contact_email')
                                                    ->label('Contact Email')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-envelope')
                                                    ->placeholder('contact@company.com')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Location')
                            ->icon('heroicon-o-map')
                            ->schema([
                                Section::make('Address Details')
                                    ->description('Complete address information for the company')
                                    ->icon('heroicon-o-map-pin')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('province')
                                                    ->label('Province')
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-map')
                                                    ->placeholder('e.g., Kigali City')
                                                    ->columnSpan(1),

                                                TextInput::make('district')
                                                    ->label('District')
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-map')
                                                    ->placeholder('e.g., Gasabo')
                                                    ->columnSpan(1),

                                                TextInput::make('sector')
                                                    ->label('Sector')
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-map')
                                                    ->placeholder('e.g., Kimironko')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('cell')
                                                    ->label('Cell')
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-map')
                                                    ->placeholder('e.g., Kibagabaga'),

                                                TextInput::make('village')
                                                    ->label('Village')
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-map')
                                                    ->placeholder('e.g., Amarembo'),
                                            ]),
                                    ]),

                                // Map preview section — description() renders the helper text natively
                                Section::make('Location Preview')
                                    ->description('Map integration can be added here')
                                    ->icon('heroicon-o-map')

                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('Branding & Settings')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Section::make('Company Logo')
                                    ->description('Upload your company logo for branding')
                                    ->icon('heroicon-o-photo')
                                    ->schema([
                                        FileUpload::make('logo')
                                            ->label('Logo')
                                            ->image()
                                            ->maxSize(2048)
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                            ])
                                            ->directory('company-logos')
                                            ->visibility('public')
                                            ->imagePreviewHeight('150')
                                            ->loadingIndicatorPosition('left')
                                            ->removeUploadedFileButtonPosition('right')
                                            ->uploadButtonPosition('left')
                                            ->uploadProgressIndicatorPosition('left')
                                            ->downloadable()
                                            ->openable()
                                            ->helperText('Upload a square image for best results. Max size: 2MB'),
                                    ])
                                    ->collapsible(),

                                Section::make('Company Status')
                                    ->description('Toggle to activate or deactivate the company. Inactive companies will be restricted from system access.')
                                    ->icon('heroicon-o-shield-check')
                                    ->schema([
                                        Toggle::make('is_active')
                                            ->label('Active Company')
                                            ->required()
                                            ->default(true)
                                            ->inline(false)
                                            ->helperText('Inactive companies cannot access the system')
                                            ->onColor('success')
                                            ->offColor('danger'),
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->activeTab(1),
            ]);
    }
}
