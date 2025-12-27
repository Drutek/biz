<?php

namespace App\Enums;

enum ProductType: string
{
    case Book = 'book';
    case SaasApp = 'saas_app';
    case Course = 'course';
    case Template = 'template';
    case DigitalDownload = 'digital_download';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Book => 'Book',
            self::SaasApp => 'SaaS App',
            self::Course => 'Course',
            self::Template => 'Template',
            self::DigitalDownload => 'Digital Download',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Book => 'amber',
            self::SaasApp => 'blue',
            self::Course => 'purple',
            self::Template => 'green',
            self::DigitalDownload => 'cyan',
            self::Other => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Book => 'book-open',
            self::SaasApp => 'code-bracket',
            self::Course => 'academic-cap',
            self::Template => 'document-duplicate',
            self::DigitalDownload => 'arrow-down-tray',
            self::Other => 'cube',
        };
    }
}
