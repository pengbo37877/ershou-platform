<?php

namespace App\Admin\Extensions\Tools;

class OnSaleSku extends \Encore\Admin\Grid\Tools\BatchAction
{
    protected $action;

    public function __construct($action = 1)
    {
        $this->action = $action;
    }

    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {

    $.ajax({
        method: 'post',
        url: '{$this->resource}/on_sale',
        data: {
            _token:LA.token,
            ids: $.admin.grid.selected(),
            action: {$this->action}
        },
        success: function () {
            $.pjax.reload('#pjax-container');
            toastr.success('操作成功');
        }
    });
});

EOT;

    }
}