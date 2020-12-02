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
    const VERSION = "1.5.0";

    /**
     * get list of domains with active whois privacy service from HEXONET API
     * @return array list of domains
     */
    private function getIdProtectedDomainsAPI()
    {
        $r = Ispapi::call([
            "COMMAND" => "QueryDomainList",
            "X-ACCEPT-WHOISTRUSTEE-TAC" => 1,
            "USERDEPTH" => "SELF"
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
            "idprotection" => 1,
            "status" => "active"
        ])->pluck("domain");
    }

    /**
     * get list of domains with inactive whois privacy service from HEXONET API
     * @return array list of domains
     */
    private function getTransferUnlockedDomainsAPI()
    {
        $r = Ispapi::call([
            "COMMAND" => "QueryDomainList",
            "TRANSFERLOCK" => 0,
            "USERDEPTH" => "SELF"
        ]);
        if ($r["CODE"] !== "200" || !$r["PROPERTY"]["COUNT"][0]) {
            return [];
        }
        return $r["PROPERTY"]["DOMAIN"];
    }

    /**
     * get list of domains with status active
     * @return array list of domains
     */
    private function getActiveDomainsWHMCS()
    {
        $result = DB::table("tbldomains")
            ->select("id", "domain", "idprotection", "additionalnotes", "is_premium")
            ->where([
                ["registrar", "=", "ispapi"],
                ["status", "=", "active"]
            ])
            ->get();
        $tmp = [];
        foreach ($result as $row) {
            if (is_object($row)) {
                $tmp[$row->domain] = get_object_vars($row);
            } else {
                $tmp[$row["domain"]] = $row;
            }
        }
        return $tmp;
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
        $domainsWHMCS = $this->getActiveDomainsWHMCS();
        // --- gather all domain names with active whois privacy service in API but not in WHMCS
        $items = [];
        $casesAPI = $this->getIdProtectedDomainsAPI();
        foreach ($casesAPI as $c) {
            if (isset($domainsWHMCS[$c]) && $domainsWHMCS[$c]["idprotection"] === 0) {
                $items[] = $c;
            }
        }
        if (!empty($items)) {
            $data["wpapicase"] = $items;
        }
        // --- gather all domain names with inactive transferlock in API (WHMCS does not support transferlock yet)
        $items = [];
        $casesAPI = $this->getTransferUnlockedDomainsAPI();
        foreach ($casesAPI as $c) {
            if (isset($domainsWHMCS[$c])) {
                $items[] = $c;
            }
        }
        if (!empty($items)) {
            $data["tlapicase"] = $items;
        }
        // --- gather all WHMCS domain names with status active and additional notes related to migration tool
        $items = [];
        foreach ($domainsWHMCS as $c => $d) {
            if (preg_match("/^INIT_TRANSFER_(SUCCESS|FAIL)$/i", $d["additionalnotes"])) {
                $items[] = $c;
            }
        }
        if (!empty($items)) {
            $data["migrationcase"] = $items;
        }
        // --- all premium domain names with missing registrarRenewalCost in tbldomains_extra
        $items = [];
        foreach ($domainsWHMCS as $c => $d) {
            if ($d["is_premium"] === 1) {
                $recurringamount = \WHMCS\Domain\Extra::whereDomainId($d["id"])->whereName("registrarRenewalCostPrice")->value("value");
                if (is_null($recurringamount)) {
                    $items[] = $c;
                }
            }
        }
        if (!empty($items)) {
            $data["registrarrenewalcostpricezerocase"] = $items;
        }
        return $data;
    }

    /**
     * get case label
     * @param String $case case id
     * @param int $count count of affected items
     * @return String case label
     */
    private function getCaseLabel($case, $count)
    {
        if ($case === "wpapicase") {
            $label = "Domain" . (($count === 1) ? "" : "s");
            return "<b>{$label} found with ID Protection Service active only on Registrar-side.</b>";
        }
        if ($case === "tlapicase") {
            $label = "Domain" . (($count === 1) ? "" : "s");
            return "<b>{$label} found with inactive transferlock.</b>";
        }
        if ($case === "migrationcase") {
            $label = "Domain" . (($count === 1) ? "" : "s");
            return "<b>{$label} found with migration process related additional notes.</b>";
        }
        if ($case === "registrarrenewalcostpricezerocase") {
            $label = "Premium Domain" . (($count === 1) ? "" : "s");
            return "<b>{$label} found with missing Premium Renewal Cost Price in DB.</b>";
        }
        return "";
    }

    /**
     * get case description
     * @param String $case case id
     * @param int $count count of affected items
     * @return String case description
     */
    private function getCaseDescription($case, $count)
    {
        if ($case === "wpapicase") {
            $label = "Domain" . (($count === 1) ? "" : "s");
            return "We found <b>{$count} {$label}</b> with active ID Protection in HEXONET's System, but inactive in WHMCS. Therefore, your clients are using that service, but they are not getting invoiced for it by WHMCS.<br/><br/>Use the button &quot;CSV&quot; to download the list of affected items and use the below button &quot;Fix this!&quot; to disable that service for the listed domain names in HEXONET's System.";
        }
        if ($case === "tlapicase") {
            $label = "Domain" . (($count === 1) ? "" : "s");
            return "We found <b>{$count} {$label}</b> with inactive transferlock in HEXONET's System. Activating it avoids domains getting transferred way in ease. Transferlock is free of charge!<br/><br/>Use the button &quot;CSV&quot; to download the list of affected items and use the below button &quot;Fix this!&quot; to activate transferlock for the listed domain names.";
        }
        if ($case === "migrationcase") {
            $label = "Domain" . (($count === 1) ? "" : "s");
            return "We found <b>{$count} {$label}</b> with migration process related additional notes. Our whmcs-based migration tool uses the additional notes field for processing that can be cleaned up for domains in status active. Usually you'll find additional notes set to INIT_TRANSFER_FAIL or INIT_TRANSFER_SUCCESS.<br/><br/>Use the button &quot;CSV&quot; to download the list of affected items and use the below button &quot;Fix this!&quot; to process the cleanup.";
        }
        if ($case === "registrarrenewalcostpricezerocase") {
            $label = "Premium Domain" . (($count === 1) ? "" : "s");
            return "We found <b>{$count} {$label}</b> with missing Premium Renewal Cost Price in DB. There had been a WHMCS Core Bug that got patched around WHMCS v7.8. It also affected our High-Performance Domainchecker Add-On's Premium Domain Handling.";
        }
        return "";
    }

    /**
     * get html block of case
     * @param String $case case id
     * @param array $rows data rows of case
     * @return String html code
     */
    private function getCaseBlock($case, $rows)
    {
        $count = count($rows);
        $items = implode(", ", $rows);
        $label = $this->getCaseLabel($case, $count);
        $descr = $this->getCaseDescription($case, $count);
        return <<<EOF
            <div class="alert alert-danger" id="{$case}">
                <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#monitModal" data-case="{$case}" data-count="{$count}" data-items="{$items}" data-label="{$label}" data-descr="{$descr}">
                    Details!
                </button>
                <b>{$count}</b> {$label}
            </div>
EOF;
    }

    /**
     * fix given single item of given case
     * @param String $case case id
     * @param String $item object id like domain name
     * @return array result e.g. [ "success" => true, "msg" => "200 Command completed successfully, "case" => "tlwhmcscase", "item" => "100works.com" ]
     */
    private function fixCase($case, $item)
    {
        if ($case === "wpapicase") {
            $r1 = Ispapi::call([
                "COMMAND" => "ModifyDomain",
                "DOMAIN" => $item,
                "X-ACCEPT-WHOISTRUSTEE-TAC" => 0
            ]);
            if ($r1["CODE"] == "200") {
                Ispapi::call([
                    "COMMAND" => "StatusDomain",
                    "DOMAIN" => $item
                ]);
            }
            return [
                "success" => $r1["CODE"] === "200",
                "msg" => $r1["CODE"] . " " . $r1["DESCRIPTION"],
                "case" => $case,
                "item" => $item
            ];
        }
        if ($case === "tlapicase") {
            $r1 = Ispapi::call([
                "COMMAND" => "ModifyDomain",
                "DOMAIN" => $item,
                "TRANSFERLOCK" => 1
            ]);
            if ($r1["CODE"] == "200") {
                Ispapi::call([
                    "COMMAND" => "StatusDomain",
                    "DOMAIN" => $item
                ]);
            }
            return [
                "success" => $r1["CODE"] === "200",
                "msg" => $r1["CODE"] . " " . $r1["DESCRIPTION"],
                "case" => $case,
                "item" => $item
            ];
        }
        if ($case === "migrationcase") {
            $result = DB::table("tbldomains")
                ->where([
                    "registrar" => "ispapi",
                    "domain" => $item
                ])
                ->update(["additionalnotes" => ""]);
            $success = ($result > 0);
            return [
                "success" => $success,
                "msg" => ("Case " . (($success) ? "fixed" : "still open")),
                "case" => $case,
                "item" => $item
            ];
        }
        if ($case === "registrarrenewalcostpricezerocase") {
            $params = getregistrarconfigoptions("ispapi");
            $prices = ispapi_GetPremiumPrice(array_merge($params, ["domain" => $item]));
            $domain = DB::table("tbldomains")->where([
                "registrar" => "ispapi",
                "domain" => $item,
                "status" => "Active",
                "is_premium" => 1
            ])->first();
            $id = (is_object($domain)) ? $domain->id : $domain["id"];
            $success = false;
            if (!empty($prices)) {
                $extraDetails = \WHMCS\Domain\Extra::firstOrNew([
                    "domain_id" => $id,
                    "name" => "registrarRenewalCostPrice"
                ]);
                $extraDetails->value = $prices["renew"];
                $success = $extraDetails->save();
            }
            return [
                "success" => $success,
                "msg" => ("Case " . (($success) ? "fixed" : "still open")),
                "case" => $case,
                "item" => $item
            ];
        }
        return [];
    }

    /**
     * generate widget's html output
     * @param array $data input data (from getData method)
     * @return string html code
     */
    public function generateOutput($data)
    {
        $output = "";
        $case = App::getFromRequest('fixit');
        $item = App::getFromRequest('item');
        if ($case && $item) {
            return $this->fixCase($case, $item);
        }
        if (empty($data)) {
            return $this->returnOk("No issues detected.");
        }
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
        <div class="progress" id="monitModalPgb" style="display:none">
            <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0">0%</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="monitModalDismiss">Close</button>
        <a class="btn btn-primary" id="monitModalDownload" download="export.csv">CSV</a>
        <button type="button" class="btn btn-primary" id="monitModalSubmit">Fix this!</button>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
const ispapidata = {}
const ispapiresults = {}

function refreshMyWidget(widgetName, requestString, fcase, item) {
    return new Promise(function (resolve) {
        WHMCS.http.jqClient.post(
            WHMCS.adminUtils.getAdminRouteUrl('/widget/refresh&widget=' + widgetName + '&' + requestString),
            function (data) {
                resolve(JSON.parse(data.widgetOutput))
            },
            'json'
        ).fail(function () {
            resolve(getErrorResult(fcase, item));
        })
    });
}

function getErrorResult(fcase, item) {
    return {
        success: false,
        case: fcase,
        item: item,
        msg: '421 An error occurred while communicating with the server. Please try again.'
    }
}

async function processItems(fcase, items) {
    if (ispapidata.hasOwnProperty(fcase)) {
        return
    }
    $('#monitModalSubmit').prop('disabled', true);
    ispapidata[fcase] = items
    const max = items.length

    // prepare html
    $('#monitModalPgb').css('display', '')
    let processed = 0;
    const pgb = $('#monitModalPgb .progress-bar')
    pgb.attr('aria-valuenow', 0)
    pgb.css('width', '0%')
    pgb.attr('aria-valuemax', ispapidata[fcase].length)
    ispapiresults[fcase] = {};

    while (ispapidata[fcase].length) {
        let item = ispapidata[fcase].shift()
        let data = await refreshMyWidget(
            'IspapiMonitoringWidget',
            'fixit=' + encodeURIComponent(fcase) + '&item=' + encodeURIComponent(item),
            fcase,
            item
        ).catch((err) => {
            data = getErrorResult(fcase, item)
        });
        processed++
        let percentage = Math.round((100 / max) * processed)
        ispapiresults[fcase][item] = data
        pgb.attr('aria-valuenow', processed)
        pgb.css('width', percentage + '%')
        pgb.html(percentage + '%')
    }

    if (ispapidata.hasOwnProperty(fcase)) {
        delete ispapidata[fcase]
    }

    const csvdata = [];
    const keys = Object.keys(ispapiresults[fcase]);
    keys.forEach(csvitem => {
        const row = ispapiresults[fcase][csvitem]
        csvdata.push([csvitem, (row.success ? 'OK' : 'ERROR'), row.msg].join('\\t'))
    })
    delete ispapiresults[fcase]
    $('#monitModalDownload').attr(
        'href',
        'data:application/csv;charset=utf-8,' + encodeURIComponent(csvdata.join('\\r\\n'))
    )
    $('#monitModalDownload').removeClass('btn-primary').addClass('btn-success')
}
$('#monitModal').off().on('show.bs.modal', function (event) {
    $('#monitModalPgb').css('display', 'none')
    const button = $(event.relatedTarget)
    const modal = $(this)
    const itemsArr = button.data('items').split(', ')
    modal.find('.modal-title').html(button.data('label'))
    modal.find('.modal-body p.description').html(button.data('descr'))
    $('#monitModalSubmit').off().click(function () {
        processItems(button.data('case'), itemsArr)
    })
    $('#monitModalDownload').attr(
        'href',
        'data:application/csv;charset=utf-8,' + encodeURIComponent(itemsArr.join('\\r\\n'))
    )
})
$('#monitModal').on('hidden.bs.modal', function (event) {
    refreshWidget('IspapiMonitoringWidget', 'refresh=1')
})
</script>
EOF;
    }
}
