<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class AcceptReport
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.recover-reports-accept').on('click', function () {

    // Your code.
    console.log($(this).data('id'));
    $.ajax({
        method: 'post',
        url: '/admin/recover_reports/accept',
        data: {
            _token:LA.token,
            id: $(this).data('id')
        },
        success: function () {
            $.pjax.reload('#pjax-container');
            toastr.success('操作成功');
        }
    });
});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());

        return "<a class='btn btn-xs btn-success recover-reports-accept' data-id='{$this->id}'>接受</a><br><br>";
    }

    public function __toString()
    {
        return $this->render();
    }
}