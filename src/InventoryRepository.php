<?php

namespace Pdfsystems\WebDistributionSdk;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Pdfsystems\WebDistributionSdk\Dtos\Inventory;
use Pdfsystems\WebDistributionSdk\Dtos\Product;
use Pdfsystems\WebDistributionSdk\Exceptions\NotFoundException;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class InventoryRepository extends AbstractRepository
{
    /**
     * @throws UnknownProperties
     * @throws GuzzleException
     */
    public function findById(int $id): Inventory
    {
        $requestOptions = [
            'with' => [
                'item.style',
            ],
        ];

        try {
            $response = $this->client->getJson("api/inventory/$id", $requestOptions);

            return new Inventory($response);
        } catch (RequestException) {
            throw new NotFoundException("Inventory with id $id not found");
        }
    }

    /**
     * @throws UnknownProperties
     * @throws GuzzleException
     */
    public function listByProduct(Product $product): array
    {
        $requestOptions = [
            'with' => [
                'item.style',
            ],
            'item' => $product->id,
        ];

        $response = $this->client->getJson("api/item/{$product->id}/inventory", $requestOptions);

        return array_map(function (array $inventory) use ($product): Inventory {
            return new Inventory([
                'id' => $inventory['id'],
                'item' => [
                    'item_number' => $product->item_number,
                    'style' => [
                        'name' => $product->style_name,
                    ],
                    'color_name' => $product->color_name,
                ],
                'lot' => $inventory['lot'],
                'piece' => $inventory['piece'],
                'warehouse_location' => $inventory['warehouse_location'],
                'active' => true,
                'approved' => true,
                'pre_receipt' => false,
                'seconds' => $inventory['seconds'] === 1,
                'comment' => $inventory['comment'],
                'vendor_piece' => $inventory['mill_piece'],
                'quantity_on_hand' => $inventory['on_hand'],
                'quantity_available' => $inventory['on_hand'] - $inventory['allocated'],
            ]);
        }, $response);
    }
}