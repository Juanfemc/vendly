<?php

namespace App\Support;

class StoreTemplateCatalog
{
    public const STORE = 'store';
    public const TECHNOLOGY = 'technology';
    public const FASHION = 'fashion';
    public const RESTAURANT = 'restaurant';

    public static function all(): array
    {
        return [
            self::STORE => [
                'key' => self::STORE,
                'name' => 'Tienda normal',
                'business_type' => 'store',
                'subtitle' => 'Plantilla flexible para vender productos de cualquier categoria.',
                'description' => 'Incluye catalogo ordenado, categorias destacadas, carrito lateral, checkout simple y compra por WhatsApp.',
                'features' => ['Catalogo flexible', 'Categorias destacadas', 'Carrito lateral', 'Compra por WhatsApp'],
                'available' => true,
            ],
            self::TECHNOLOGY => [
                'key' => self::TECHNOLOGY,
                'name' => 'Tecnología',
                'business_type' => 'technology',
                'subtitle' => 'Plantilla minimalista para catálogos de tecnología.',
                'description' => 'Incluye portada amplia, categorías horizontales, tarjetas limpias, producto detallado, checkout y carrito lateral.',
                'features' => ['Portada visual', 'Catálogo moderno', 'Carrito lateral', 'Checkout optimizado'],
                'available' => false,
            ],
            self::FASHION => [
                'key' => self::FASHION,
                'name' => 'Ropa',
                'business_type' => 'fashion',
                'subtitle' => 'Plantilla editorial para moda, ropa y accesorios.',
                'description' => 'Incluye portada inmersiva, grilla editorial de novedades y bloques promocionales para colecciones.',
                'features' => ['Hero editorial', 'Nuevas llegadas', 'Colecciones destacadas', 'Promos visuales'],
                'available' => true,
            ],
            self::RESTAURANT => [
                'key' => self::RESTAURANT,
                'name' => 'Comida',
                'business_type' => 'restaurant',
                'subtitle' => 'Plantilla tipo menu para restaurantes, comidas rapidas y cafeterias.',
                'description' => 'Incluye portada gastronomica, categorias de carta, platos destacados y pedido rapido por WhatsApp.',
                'features' => ['Menu por categorias', 'Platos destacados', 'Pedido por WhatsApp', 'Acciones rapidas'],
                'available' => false,
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
