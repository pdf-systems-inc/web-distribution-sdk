<?php

namespace Pdfsystems\WebDistributionSdk;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Pdfsystems\WebDistributionSdk\Dtos\Allocation;
use Pdfsystems\WebDistributionSdk\Dtos\Company;
use Pdfsystems\WebDistributionSdk\Dtos\Inventory;
use Pdfsystems\WebDistributionSdk\Dtos\Transaction;
use Pdfsystems\WebDistributionSdk\Dtos\TransactionItem;
use Pdfsystems\WebDistributionSdk\Exceptions\NotFoundException;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class TransactionRepository extends AbstractRepository
{
    /**
     * @throws UnknownProperties
     * @throws GuzzleException
     */
    public function findByTransactionNumber(Company $company, string $transactionNumber): Transaction
    {
        $requestOptions = [
            'with' => [
                'customer.country',
                'customer.primaryAddress.country',
                'customer.primaryAddress.state',
                'holds.hold',
                'items.allocatedPieces.piece.warehouse',
                'items.item.style.millUnit',
                'items.item.style.productCategoryCode',
                'items.item.style.sellingUnit',
                'rep1',
                'shipToCountry',
                'shipToState',
                'specifier.country',
                'specifier.primaryAddress.country',
                'specifier.primaryAddress.state',
            ],
            'company' => $company->id,
            'transaction_number' => $transactionNumber,
            'transaction_number_exact' => true,
        ];

        try {
            $response = $this->client->getJson("api/transaction", $requestOptions);

            return new Transaction($response[0]);
        } catch (RequestException) {
            throw new NotFoundException("Transaction with number $transactionNumber not found");
        }
    }

    /**
     * @throws GuzzleException
     */
    public function unallocate(TransactionItem|int $item): void
    {
        if (is_int($item)) {
            $this->client->post("api/transaction-item/$item/unallocate");
        } else {
            $this->client->post("api/transaction-item/$item->id/unallocate");
        }
    }

    /**
     * @throws GuzzleException
     */
    public function allocateSingle(TransactionItem $item, Inventory $piece): void
    {
        $this->allocateSingleId($item->id, $piece->id, $item->quantity_ordered);
    }

    /**
     * @throws GuzzleException
     */
    public function allocateSingleId(int $itemId, int $pieceId, float $quantity): void
    {
        $this->allocateId($itemId, [
            $pieceId => $quantity,
        ]);
    }

    /**
     * @param TransactionItem $item
     * @param Allocation[] $allocations
     * @return void
     * @throws GuzzleException
     */
    public function allocate(TransactionItem $item, array $allocations): void
    {
        $allocationMap = [];

        foreach ($allocations as $allocation) {
            $allocationMap[$allocation->inventory_id] = $allocation->quantity;
        }

        $this->allocateId($item->id, $allocationMap);
    }

    /**
     * @param int $itemId
     * @param array $allocations
     * @return void
     * @throws GuzzleException
     */
    public function allocateId(int $itemId, array $allocations): void
    {
        $this->client->post("api/transaction-item/$itemId/reallocate", $allocations);
    }
}
