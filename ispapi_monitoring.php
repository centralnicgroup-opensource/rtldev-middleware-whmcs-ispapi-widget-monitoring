<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
namespace WHMCS\Module\Widget;

use App;
use Illuminate\Database\Capsule\Manager as DB;
use WHMCS\Module\Registrar\Ispapi\Ispapi;

/**
 * WHMCS ISPAPI Account Dashboard Widget
 *
 * This Widget allows to display and fix domain data differences in WHMCS compared to the HEXONET API.
 *
 * @see https://github.com/hexonet/whmcs-ispapi-widget-monitoring/wiki/
 *
 * @copyright Copyright (c) Kai Schwarz, HEXONET GmbH, 2019
 * @license https://github.com/hexonet/whmcs-ispapi-widget-monitoring/blob/master/LICENSE/ MIT License
 */

add_hook('AdminHomeWidgets', 1, function () {
    return new IspapiMonitoringWidget();
});

/**
 * ISPAPI Monitoring Widget.
 */
class IspapiMonitoringWidget extends \WHMCS\Module\AbstractWidget
{
    protected $title = 'HEXONET ISPAPI Domain Monitoring';
    protected $description = '';
    protected $weight = 150;
    protected $columns = 1;
    protected $cache = false;
    protected $cacheExpiry = 120;
    protected $requiredPermission = '';
    const VERSION = "0.0.0";

    /**
     * get list of domains with active whois privacy service from HEXONET API
     * @return array list of domains
     */
    private function getIdProtectedDomainsAPI()
    {
        $r = Ispapi::call([
            "COMMAND" => "QueryDomainList",
            "X-ACCEPT-WHOISTRUSTEE-TAC" => 1
        ], null, 'hexonet');
        if ($r["CODE"] !== "200") {
            return [];
        }
        return $r["PROPERTY"]["OBJECTID"];
    }

    /**
     * get list of domains with active whois privacy service in WHMCS
     * @return array list of domains
     */
    private function getIdProtectedDomainsWHMCS()
    {
        return DB::table("tbldomains")->where([
            "registrar" => "hexonet",
            "idprotection" => 1
        ])->pluck("domain");
    }

    /**
     * return html code for error case specified by given error message
     * @param string $errMsg error message to show
     * @return string html code
     */
    private function returnError($errMsg)
    {
        return <<<EOF
                <div class="widget-content-padded widget-billing">
                    <div class="color-pink">$errMsg</div>
                </div>
EOF;
    }

    /**
     * return html code for ok case specified by given message
     * @param string $msg error message to show
     * @return string html code
     */
    private function returnOk($msg)
    {
        return <<<EOF
                <div class="widget-content-padded widget-billing">
                    <div class="color-greenk">$msg</div>
                </div>
EOF;
    }


    /**
     * Fetch data that will be provided to generateOutput method
     * @return array|null data array or null in case of an error
     */
    public function getData()
    {
        $data = [];
        // --- gather all domain names with active whois privacy service in API but not in WHMCS
        $diff = [];
        $casesAPI = $this->getIdProtectedDomainsAPI();
        $casesWHMCS = $this->getIdProtectedDomainsWHMCS();
        foreach ($casesAPI as $c) {//casesWHMCS is a collection!
            if (!in_array($c, $casesWHMCS)) {
                $diff[] = $c;
            }
        }
        if (!empty($diff)) {
            $data["wpapicase"] = $diff;
        }
        // --- gather all domain names with active whois privacy service in WHMCS but not in API
        return $data;
    }

    private function getCaseLabel($id, $items, $count)
    {
        if ($id === "wpapicase") {
             return "Domains found with Whois Privacy Service active only on Registrar-side.";
        }
        return "";
    }

    private function getCaseDescription($id, $count)
    {
        if ($id === "wpapicase") {
             return "We found <u>{$count} Domain Names</u> with active ID Protection in Registrar's Systems, but inactive in WHMCS. Therefore, your clients are using that service, but they are not getting invoiced for it by WHMCS. Using the below button `Fix this!` disables that service for the listed domain names in Registrar's Systems.";
        }
        return "";
    }

    private function getCaseBlock($id, $rows)
    {
        $count = count($rows);
        $items = implode(", ", $rows);
        $label = $this->getCaseLabel($id, $items, $count);
        $descr = $this->getCaseDescription($id, $count);
        return <<<EOF
            <div class="alert alert-danger" id="{$id}">
                <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#monitModal" data-case="{$id}" data-count="{$count}" data-items="{$items}" data-label="{$label}" data-descr="{$descr}">
                    Show me!
                </button>
                {$count} {$label}
            </div>
EOF;
    }

    private function fixCase($case)
    {
        $items = [];
        $max = 0;
        do {
            $item = App::getFromRequest("item" . $max);
            if ($item) {
                $items[] = $item;
                $max++;
            }
        } while ($item);

        if ($case === "wpapicase") {
            foreach ($items as $idx => $item) {
                $r = Ispapi::call([
                    "COMMAND" => "ModifyDomain",
                    "DOMAIN" => $item,
                    "X-ACCEPT-WHOISTRUSTEE-TAC" => 0
                ], null, "hexonet");
                if ($r["CODE"] == "200") {//to get domain list cache refreshed
                    Ispapi::call([
                        "COMMAND" => "StatusDomain",
                        "DOMAIN" => $item
                    ], null, "hexonet");
                }
                if ($idx < $max) {
                    sleep(1);
                }
            }
        }
    }

    /**
     * generate widget's html output
     * @param array $data input data (from getData method)
     * @return string html code
     */
    public function generateOutput($data)
    {
        $case = App::getFromRequest('fixit');
        if ($case) {
            $this->fixCase($case);
        }
        if (empty($data)) {
            return $this->returnOk("No issues detected.");
        }
        $output = "";
        foreach ($data as $key => $rows) {
            $output .= $this->getCaseBlock($key, $rows);
        }
        return <<<EOF
<div class="widget-content-padded ispapi-monitoring-items">{$output}</div>
<div class="modal fade" id="monitModal" tabindex="-1" role="dialog" aria-labelledby="monitModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="monitModalLabel"></h2>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="monitModalForm">
          <div class="form-group">
            <label for="recipient-name" class="col-form-label">Details:</label>
            <p class="description"></p>
          </div>
          <div class="form-group">
            <label for="message-text" class="col-form-label">Affected:</label>
            <textarea class="form-control" id="affected-items" rows="10" readonly></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="monitModalDismiss">Close</button>
        <a class="btn btn-primary" id="monitModalDownload" download="export.csv">CSV</a>
        <button type="button" class="btn btn-primary" id="monitModalSubmit">Fix this!</button>
      </div>
    </div>
  </div>
</div>
<script>
$('#monitModal').off().on('show.bs.modal', function (event) {
  const button = $(event.relatedTarget)
  const modal = $(this)
  const items = button.data('items')
  const itemsArr = items.split(', ')
  modal.find('.modal-title').text(button.data('label'))
  modal.find('.modal-body p.description').html(button.data('descr'))
  modal.find('.modal-body textarea').val(items)
  $('#monitModalSubmit').off().click(function() {
      let url =  'fixit=' + button.data('case');
      for (let i=0; i<itemsArr.length; i++){
          url += ('&item' + i + '=' + encodeURIComponent(itemsArr[i]))
      }
      refreshWidget('IspapiMonitoringWidget', url)
      $('#monitModalDismiss').click()
  })
  $('#monitModalDownload').css('display', '')
  if (!itemsArr.length){
      $('#monitModalDownload').css('display', 'none')
      return
  }
  $('#monitModalDownload').attr(
      'href',
      'data:application/csv;charset=utf-8,' + encodeURIComponent(itemsArr.join('\\r\\n'))
  )
})
</script>
EOF;
    }
}
