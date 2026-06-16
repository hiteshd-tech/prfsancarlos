<?php
/**
 * Campaign Builder Toolbar Template
 *
 * @package   Charitable/Admin/Views
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="charitable-toolbar">

    <div class="charitable-left">
        <div class="badge">
            <a href="https://www.wpcharitable.com?utm_source=WordPress&amp;utm_campaign=WP+Charitable&amp;utm_medium=Welcome+Page+Icon&amp;utm_content=Icon" target="_blank"><i class="charitable-icon charitable-icon-charitable"></i></a>
        </div>
    </div>

    <div class="charitable-center">


    </div>

    <div class="charitable-right">

        <button id="charitable-help" class="charitable-btn charitable-btn-toolbar charitable-btn-light-grey" title="Help Ctrl+H">
                <i class="fa fa-question-circle-o"></i><span>Help</span>
        </button>


        <button id="charitable-exit" title="Exit Ctrl+Q">
            <i class="fa fa-times"></i>
        </button>

    </div>

</div>