<?php

namespace App\Admin\Extensions;


use App\UserAddress;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Maatwebsite\Excel\Facades\Excel;

class ZtoExport extends AbstractExporter
{

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        Excel::load('public/excel/zto-order.xlsx', function($excel) {
            $excel->sheet('下单模板', function($sheet) {
                // 这段逻辑是从表格数据中取出需要导出的字段
                $rows = collect($this->getData())->map(function ($item) {
                    $address = UserAddress::withTrashed()->find(array_get($item, 'address_id'))->toArray();
//                    return array_only($address, ['contact_name', 'contact_phone', 'province', 'city', 'district', 'address']);
                    return [
                        $address['contact_name'],
                        $address['contact_phone'],
                        '',
                        $address['province'].$address['city'].$address['district'].$address['address'],
                        '书本',
                    ];
                });
                for ($i=0;$i<count($rows);$i++) {
                    $sheet->row($i+2, $rows[$i]);
                }
            });
        })->export('xls');
    }
}