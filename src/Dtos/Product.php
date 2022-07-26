<?php

namespace Pdfsystems\WebDistributionSdk\Dtos;

use Spatie\DataTransferObject\Attributes\MapFrom;

class Product extends AbstractDto
{
    public int $id;

    public string $item_number;

    #[MapFrom('style.name')]
    public string $style_name;

    public ?string $color_name;

    #[MapFrom('style.product_category_code.name')]
    public string $category;

    #[MapFrom('style.content')]
    public ?string $content;

    #[MapFrom('style.width')]
    public ?string $width;

    #[MapFrom('style.repeat')]
    public ?string $repeat;

    #[MapFrom('style.primary_price.wholesale_price')]
    public ?float $price;

    public ?Company $company;

    public ?Line $line;
}
