<?php

namespace App\Enum\Permissions;

enum ProductEnum: string
{
    case Product         = 'product';
    case ProductViewAny  = 'product.view-any';
    case ProductView     = 'product.view';
    case ProductCreate   = 'product.create';
    case ProductUpdate   = 'product.update';
    case ProductInactive = 'product.inactive';
    case ProductSync     = 'product.sync';

    public function label(): string
    {
        return match ($this) {
            self::Product         => "Product Access",
            self::ProductViewAny  => "View Product List",
            self::ProductView     => "View Product Details",
            self::ProductCreate   => "Create Product",
            self::ProductUpdate   => "Update Product",
            self::ProductInactive => "Mark Product as Inactive",
            self::ProductSync     => "Sync Product Data",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Product Permissions',
            'permissions' => array_reduce(self::cases(), function ($carry, $enum) {
                $carry[$enum->value] = $enum->label();
                return $carry;
            }, []),
        ];
    }

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
