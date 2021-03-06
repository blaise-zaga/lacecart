<?php
/**
*  2015 Lace Cart
*
*  @author LaceCart Dev <info@lacecart.com.ng>
*  @copyright  2015 LaceCart Team
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of LaceCart Team
**/

namespace LaceCart\Backend;

class DashboardController extends ControllerBase
{
    public function index()
    {
        $this->setView('users/dashboard');
        $this->view->title = 'Dashboard';
        $this->response->setBody($this->view->render());
        $this->response->send();
    }

    public function test()
    {
        echo "here";
    }
}