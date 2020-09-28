<?php

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

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
namespace WHMCS\Module\Widget;

use App;
use Illuminate\Database\Capsule\Manager as DB;
use WHMCS\Module\Registrar\Ispapi\Ispapi;

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
    const VERSION = "1.1.1";

    /**
     * get list of domains with active whois privacy service from HEXONET API
     * @return array list of domains
     */
    private function getIdProtectedDomainsAPI()
    {
        $r = Ispapi::call([
            "COMMAND" => "QueryDomainList",
            "X-ACCEPT-WHOISTRUSTEE-TAC" => 1
        ]);
        if ($r["CODE"] !== "200" || !$r["PROPERTY"]["COUNT"][0]) {
            return [];
        }
        return $r["PROPERTY"]["DOMAIN"];
    }

    /**
     * get list of domains with active whois privacy service in WHMCS
     * @return array list of domains
     */
    private function getIdProtectedDomainsWHMCS()
    {
        return DB::table("tbldomains")->where([
            "registrar" => "ispapi",
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
                    <div class="item text-center">
                        <div class="data color-green">$msg</div>
                        <div class="note">Check Result</div>
                    </div>
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
            $label = "Domain" . ((count($items) === 1) ? "" : "s");
            return "<b>{$label} found with ID Protection Service active only on Registrar-side.</b>";
        }
        return "";
    }

    private function getCaseDescription($id, $count)
    {
        if ($id === "wpapicase") {
            $label = "Domain" . (($count === 1) ? "" : "Name");
            return "We found <b>{$count} {$label}</b> with active ID Protection in HEXONET's System, but inactive in WHMCS. Therefore, your clients are using that service, but they are not getting invoiced for it by WHMCS.<br/><br/>Use the button &quot;CSV&quot; to download the list of affected items and use the below button &quot;Fix this!&quot; to disable that service for the listed domain names in HEXONET's System.";
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
                    Details!
                </button>
                {$count} {$label}
            </div>
EOF;
    }

    private function fixCase($case, $items)
    {
        $max = count($items);

        if ($case === "wpapicase") {
            foreach ($items as $idx => $item) {
                $r = Ispapi::call([
                    "COMMAND" => "ModifyDomain",
                    "DOMAIN" => $item,
                    "X-ACCEPT-WHOISTRUSTEE-TAC" => 0
                ]);
                if ($r["CODE"] == "200") {//to get domain list cache refreshed
                    Ispapi::call([
                        "COMMAND" => "StatusDomain",
                        "DOMAIN" => $item
                    ]);
                }
                if ($idx < $max) {
                    sleep(1);
                }
            }
        }

        return $this->getData();
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
            $items = isset($data[$case]) ? $data[$case] : [];
            if (!empty($items)){
                $data = $this->fixCase($case, $items);
            }
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
  const itemsArr = button.data('items').split(', ')
  modal.find('.modal-title').html(button.data('label'))
  modal.find('.modal-body p.description').html(button.data('descr'))
  $('#monitModalSubmit').off().click(function() {
      refreshWidget('IspapiMonitoringWidget', 'fixit=' + button.data('case'))
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
