<?php
namespace lib;

/**
 * Класс для работы с выгрузкой остатков Autonix
 *
 * Class Autonix
 * @property  file
 * @package lib
 */
class Autonix
{
    private $fileDir = '/import/autonix/';

    /**
     * Autonix constructor.
     *
     * @param $file
     * @param $section
     */
    public function __construct($file, $section)
    {
        $this->file = $file;
        $this->section = $section;
    }

    /**
     * Запуск обработчика
     *
     */
    public function run()
    {
        if ($this->file && $this->section) {
            $csv = file_get_contents($this->file);
            $arCsv = explode(PHP_EOL, $csv);
            $arItems = [];

            $this->resetRemains();

            foreach ($arCsv as $key => $arString) {
                $expStr[$key] = explode(';', $arString);
                $quantity = intval($expStr[$key][2]);
                if (is_numeric($quantity)) {
                    $arItems[$expStr[$key][0]] = str_replace(',', '', $quantity);
                }
            }

            $elements = $this->getElements(array_keys($arItems));

            foreach ($elements as $id => $arElement) {
                if (!empty($arItems[$arElement['PROPERTY_ART_VALUE']])) {
                    $amount = intval($arItems[$arElement['PROPERTY_ART_VALUE']]);
                    $this->updateAmount($arElement['ID'], $amount, 1);
                }
            }
        }
    }

    /**
     * обнуление остатков на складе
     */
    private function resetRemains()
    {
        $res = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID'           => 6,
                'INCLUDE_SUBSECTIONS' => 'Y',
                'SECTION_ID'          => $this->section,
                'ACTIVE'              => 'Y',
                'ACTIVE_DATE'         => 'Y',
            ],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
            ]
        );
        while ($arRes = $res->GetNext()) {
            $result[] = $arRes;
        }

        foreach ($result as $key => $item) {
            $this->updateAmount($item['ID'], 0, 1);
        }
    }

    /**
     * @param array $articles
     *
     * @return mixed
     */
    private function getElements(array $articles)
    {
        $res = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID'           => 6,
                'INCLUDE_SUBSECTIONS' => 'Y',
                'ACTIVE'              => 'Y',
                'SECTION_ID'          => $this->section,
                'PROPERTY_ART'        => $articles,
            ],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
                'PROPERTY_ART',
            ]
        );
        while ($arRes = $res->GetNext()) {
            $result[$arRes['ID']] = $arRes;
        }

        return $result;
    }

    /**
     * обновление остатков на складе
     *
     * @param int $id
     * @param int $amount
     * @param int $store
     */
    private function updateAmount(int $id, int $amount, int $store)
    {
        $rsStore = \CCatalogStoreProduct::GetList(
            [],
            [
                'PRODUCT_ID' => $id,
                'STORE_ID'   => $store,
            ],
            false,
            false,
            [
                'ID',
                'PRODUCT_ID',
                'AMOUNT',
            ]
        )->Fetch();

        if (!empty($rsStore['ID'])) {
            \CCatalogStoreProduct::Update(
                $rsStore['ID'],
                [
                    'PRODUCT_ID' => $id,
                    'STORE_ID'   => $store,
                    'AMOUNT'     => $amount
                ]
            );
        } else {
            \CCatalogStoreProduct::Add(
                [
                    'PRODUCT_ID' => $id,
                    'STORE_ID'   => $store,
                    'AMOUNT'     => $amount,
                ]
            );
        }
    }

    /**
     * @return bool|string
     */
    private function getFile()
    {
        $scannedDirectory = array_diff(scandir($_SERVER['DOCUMENT_ROOT'].$this->fileDir), ['..', '.']]);
        $file = $_SERVER['DOCUMENT_ROOT'].$this->fileDir.$scannedDirectory[2];

        if (file_exists($file)) {
            return $file;
        } else {
            return false;
        }
    }

    private function dateCmp($a, $b)
    {
        return ($a[1] < $b[1]) ? -1 : 0;
    }

    private function sortByDate(&$files)
    {
        usort($files, 'dateCmp');
    }
}