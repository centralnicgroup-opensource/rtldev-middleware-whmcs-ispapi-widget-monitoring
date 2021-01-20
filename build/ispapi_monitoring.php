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
    const VERSION = "1.6.4";

    /**
     * add generic parameters to domain list command and request to API
     * @static
     * @param array $command API Command to request
     * @return array API response
     */
    private static function requestDomainListFromAPI($command)
    {
        return Ispapi::call(array_merge($command, [
            "USERDEPTH" => "SELF",
            "UNIQUE" => 1,
            "VERSION" => 2,
            "NOTOTAL" => 1
        ]));
    }

    /**
     * get list of domains with active whois privacy service from HEXONET API
     * @static
     * @return array list of domains
     */
    private static function getIdProtectedDomainsAPI()
    {
        $r = self::requestDomainListFromAPI([
            "COMMAND" => "QueryDomainList",
            "X-ACCEPT-WHOISTRUSTEE-TAC" => 1
        ]);
        if ($r["CODE"] !== "200" || !$r["PROPERTY"]["COUNT"][0]) {
            return [];
        }
        return $r["PROPERTY"]["DOMAIN"];
    }

    /**
     * get list of premium domains from HEXONET API
     * @static
     * @return array list of premium domains
     */
    private static function getPremiumDomainsAPI()
    {
        $r = self::requestDomainListFromAPI([
            "COMMAND" => "QueryDomainList",
            "SUBCLASSREGEX" => "^PREMIUM_.+$"
        ]);
        if ($r["CODE"] !== "200" || !$r["PROPERTY"]["COUNT"][0]) {
            return [];
        }
        return $r["PROPERTY"]["OBJECTID"];
    }

    /**
     * get list of domains with inactive whois privacy service from HEXONET API
     * @static
     * @return array list of domains
     */
    private static function getTransferUnlockedDomainsAPI()
    {
        $r = self::requestDomainListFromAPI([
            "COMMAND" => "QueryDomainList",
            "TRANSFERLOCK" => 0
        ]);
        if ($r["CODE"] !== "200" || !$r["PROPERTY"]["COUNT"][0]) {
            return [];
        }
        return $r["PROPERTY"]["DOMAIN"];
    }

    /**
     * get list of domains with status active
     * @static
     * @return array list of domains
     */
    private static function getActiveDomainsWHMCS()
    {
        static $tmp = null;
        if (!is_null($tmp)) {
            return $tmp;
        }
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
     * return html code for ok case specified by given message
     * @static
     * @param String $msg success message to show
     * @param String $prehtml HTML to add before the success message output
     * @param String $afterhtml HTML to add after the success message output
     * @return String html code
     */
    private static function returnOk($msg, $prehtml, $afterhtml)
    {
        return <<<EOF
                <div class="widget-content-padded widget-billing">
                    {$prehtml}
                    <div class="item text-center">
                        <div class="data color-green">$msg</div>
                        <div class="note">Check Result</div>
                    </div>
                    {$afterhtml}
                </div>
EOF;
    }

    /**
     * get data per given issue case
     * @static
     * @param String $case case id
     */
    private static function getCaseData($case, $count)
    {
        $singular = ($count === 1);
        if ($case === "wpapicase") {
            $label = "Domain" . ($singular ? "" : "s");
            return [
                "label" => "<b>{$label} found with ID Protection Service active only on Registrar-side.</b>",
                "descr" => "We found <b>{$count} {$label}</b> with active ID Protection in HEXONET's System, but inactive in WHMCS. Therefore, your clients are using that service, but they are not getting invoiced for it by WHMCS.<br/><br/>Use the button &quot;CSV&quot; to download the list of affected items and use the below button &quot;Fix this!&quot; to disable that service for the listed domain names in HEXONET's System."
            ];
        }
        if ($case === "tlapicase") {
            $label = "Domain" . ($singular ? "" : "s");
            return [
                "label" => "<b>{$label} found with inactive transferlock.</b>",
                "descr" => "We found <b>{$count} {$label}</b> with inactive transferlock in HEXONET's System. Activating it avoids domains getting transferred way in ease. Transferlock is free of charge!<br/><br/>Use the button &quot;CSV&quot; to download the list of affected items and use the below button &quot;Fix this!&quot; to activate transferlock for the listed domain names."
            ];
        }
        if ($case === "migrationcase") {
            $label = "Domain" . ($singular ? "" : "s");
            return [
                "label" => "<b>{$label} found with migration process related additional notes.</b>",
                "descr" => "We found <b>{$count} {$label}</b> with migration process related additional notes. Our whmcs-based migration tool uses the additional notes field for processing that can be cleaned up for domains in status active. Usually you'll find additional notes set to INIT_TRANSFER_FAIL or INIT_TRANSFER_SUCCESS.<br/><br/>Use the button &quot;CSV&quot; to download the list of affected items and use the below button &quot;Fix this!&quot; to process the cleanup."
            ];
        }
        if ($case === "registrarrenewalcostpricezerocase") {
            $label = "Premium Domain" . ($singular ? "" : "s");
            return [
                "label" => "<b>{$label} found with missing Premium Renewal Cost Price in DB.</b>",
                "descr" => "We found <b>{$count} {$label}</b> with missing Premium Renewal Cost Price in DB. There had been a WHMCS Core Bug that got patched around WHMCS v7.8. It also affected our High-Performance Domainchecker Add-On's Premium Domain Handling."
            ];
        }
        if ($case === "domain2premiumcase") {
            $label = "Standard Domain" . ($singular ? "" : "s");
            return [
                "label" => "<b>{$label} found that " . ($singular ? "is a" : "are" ) . " Premium Domains in HEXONET's System.</b>",
                "descr" => "We found <b>{$count} {$label}</b> in WHMCS that " . ($singular ? "is a" : "are" ) . " Premium Domains in HEXONET's System. This may happen if domains were manually added in WHMCS or the respective registry did this change."
            ];
        }
        return [
            "label" => "",
            "descr" => ""
        ];
    }

    /**
     * get html block of case
     * @static
     * @param String $case case id
     * @param array $rows data rows of case
     * @return String html code
     */
    private static function getCaseBlock($case, $rows)
    {
        $count = count($rows);
        $items = implode(", ", $rows);
        $data = self::getCaseData($case, $count);
        $label = $data["label"];
        $descr = $data["descr"];
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
     * fix single item of case `wpapicase`: domain name with active whois privacy service in API but not in WHMCS
     * @static
     * @param String $item punycode domain name to fix
     * @return array result
     */
    private static function fixWPAPICASE($item)
    {
        $r = Ispapi::call([
            "COMMAND" => "ModifyDomain",
            "DOMAIN" => $item,
            "X-ACCEPT-WHOISTRUSTEE-TAC" => 0
        ]);
        if ($r["CODE"] == "200") {
            Ispapi::call([
                "COMMAND" => "StatusDomain",
                "DOMAIN" => $item
            ]);
        }
        return [
            "success" => $r["CODE"] === "200",
            "msg" => $r["CODE"] . " " . $r["DESCRIPTION"],
            "case" => "wpapicase",
            "item" => $item
        ];
    }

    /**
     * fix single item of case `tlapicase`: domain name with inactive transferlock in API (WHMCS does not support transferlock yet)
     * @static
     * @param String $item punycode domain name to fix
     * @return array result
     */
    private static function fixTLAPICASE($item)
    {
        $r = Ispapi::call([
            "COMMAND" => "ModifyDomain",
            "DOMAIN" => $item,
            "TRANSFERLOCK" => 1
        ]);
        if ($r["CODE"] == "200") {
            Ispapi::call([
                "COMMAND" => "StatusDomain",
                "DOMAIN" => $item
            ]);
        }
        return [
            "success" => $r["CODE"] === "200",
            "msg" => $r["CODE"] . " " . $r["DESCRIPTION"],
            "case" => "tlapicase",
            "item" => $item
        ];
    }

    /**
     * fix single item of case `migrationcase`: WHMCS domain name with status active and additional notes related to migration tool
     * @static
     * @param String $item punycode domain name to fix
     * @return array result
     */
    private static function fixMIGRATIONCASE($item)
    {
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
            "case" => "migrationcase",
            "item" => $item
        ];
    }

    /**
     * fix single item of case `registrarrenewalcostpricezerocase`: premium domain name with missing registrarRenewalCost in tbldomains_extra
     * @static
     * @param String $item punycode domain name to fix
     * @return array result
     */
    private static function fixREGISTRARRENEWALCOSTPRICEZEROCASE($item)
    {
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
            "case" => "registrarrenewalcostpricezerocase",
            "item" => $item
        ];
    }

    /**
     * fix single item of case `domain2premiumcase`: standard domain being premium in real
     * @static
     * @param String $item punycode domain name to fix
     * @return array result
     */
    private static function fixDOMAIN2PREMIUMCASE($item)
    {
        $query = [
            "registrar" => "ispapi",
            "domain" => $item,
            "status" => "Active",
            "is_premium" => null
        ];
        $domain = DB::table("tbldomains")->where($query)->first();
        $id = (is_object($domain)) ? $domain->id : $domain["id"];
        $success = false;

        $params = getregistrarconfigoptions("ispapi");
        $prices = ispapi_GetPremiumPrice(array_merge($params, ["domain" => $item]));

        if (!empty($prices)) {
            $currency = \WHMCS\Billing\Currency::where("code", $prices["CurrencyCode"])->first();
            if (!$currency) {
                return [
                    "success" => false,
                    "msg" => ("Case " . (($success) ? "fixed" : "still open (Currency " . $prices["CurrencyCode"] . " not configured)")),
                    "case" => "domain2premiumcase",
                    "item" => $item
                ];
            }
            $extraDetails = \WHMCS\Domain\Extra::firstOrNew([
                "domain_id" => $id,
                "name" => "registrarCurrency"
            ]);
            $extraDetails->value = $currency->id;
            $success = $extraDetails->save();
            if ($success) {
                $extraDetails = \WHMCS\Domain\Extra::firstOrNew([
                    "domain_id" => $id,
                    "name" => "registrarCostPrice"
                ]);
                $extraDetails->value = isset($prices["register"]) ? $prices["register"] : $prices["renew"];
                $success = $extraDetails->save();

                if ($success) {
                    $extraDetails = \WHMCS\Domain\Extra::firstOrNew([
                        "domain_id" => $id,
                        "name" => "registrarRenewalCostPrice"
                    ]);
                    $extraDetails->value = $prices["renew"];
                    $success = $extraDetails->save();
                }
            }
        }
        if ($success) {
            DB::table("tbldomains")->where($query)->update(["is_premium" => 1]);
        }
        return [
            "success" => $success,
            "msg" => ("Case " . (($success) ? "fixed" : "still open")),
            "case" => "domain2premiumcase",
            "item" => $item
        ];
    }

    /**
     * fix given single item of given case
     * @static
     * @param String $case case id
     * @param String $item object id like domain name
     * @return array result e.g. [ "success" => true, "msg" => "200 Command completed successfully, "case" => "tlwhmcscase", "item" => "100works.com" ]
     */
    private static function fixCase($case, $item)
    {
        if (is_callable([self::class, "fix" . strtoupper($case)], true, $fn)) {
            return $fn($item);
        }
        return [];
    }

    /**
     * get data for case "wpapicase": all domain names with active whois privacy service in API but not in WHMCS
     * @static
     * @param array $data data container
     * @return array updated data container
     */
    private static function getDataWPAPICASE(&$data)
    {
        $domainsWHMCS = self::getActiveDomainsWHMCS();
        $items = [];
        $casesAPI = self::getIdProtectedDomainsAPI();
        foreach ($casesAPI as $c) {
            if (isset($domainsWHMCS[$c]) && empty($domainsWHMCS[$c]["is_premium"]) /* null, 0, empty str */) {
                $items[] = $c;
            }
        }
        if (!empty($items)) {
            $data["wpapicase"] = $items;
        }
    }

    /**
     * get data for case "tlapicase": all domain names with inactive transferlock in API (WHMCS does not support transferlock yet)
     * @static
     * @param array $data data container
     * @return array updated data container
     */
    private static function getDataTLAPICASE(&$data)
    {
        $domainsWHMCS = self::getActiveDomainsWHMCS();
        $items = [];
        $casesAPI = self::getTransferUnlockedDomainsAPI();
        foreach ($casesAPI as $c) {
            if (isset($domainsWHMCS[$c])) {
                $items[] = $c;
            }
        }
        if (!empty($items)) {
            $data["tlapicase"] = $items;
        }
    }

    /**
     * get data for case "migrationcase": all WHMCS domain names with status active and additional notes related to migration tool
     * @static
     * @param array $data data container
     * @return array updated data container
     */
    private static function getDataMIGRATIONCASE(&$data)
    {
        $domainsWHMCS = self::getActiveDomainsWHMCS();
        $items = [];
        foreach ($domainsWHMCS as $c => $d) {
            if (preg_match("/^INIT_TRANSFER_(SUCCESS|FAIL)$/i", $d["additionalnotes"])) {
                $items[] = $c;
            }
        }
        if (!empty($items)) {
            $data["migrationcase"] = $items;
        }
    }

    /**
     * get data for case "registrarrenewalcostpricezerocase": all premium domain names with missing registrarRenewalCost in tbldomains_extra
     * @static
     * @param array $data data container
     * @return array updated data container
     */
    private static function getDataREGISTRARRENEWALCOSTPRICEZEROCASE(&$data)
    {
        $domainsWHMCS = self::getActiveDomainsWHMCS();
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
    }

    /**
     * get data for case "domain2premiumcase": standard domain being premium in real
     * @static
     * @param array $data data container
     * @return array updated data container
     */
    private static function getDataDOMAIN2PREMIUMCASE(&$data)
    {
        $domainsWHMCS = self::getActiveDomainsWHMCS();
        $casesAPI = self::getPremiumDomainsAPI();
        $items = [];
        foreach ($casesAPI as $c) {
            if (isset($domainsWHMCS[$c]) && empty($domainsWHMCS[$c]["is_premium"]) /* null, 0, empty str */) {
                $items[] = $c;
            }
        }
        if (!empty($items)) {
            $data["domain2premiumcase"] = $items;
        }
    }

    /**
     * Fetch data that will be provided to generateOutput method
     * @return array|null data array or null in case of an error
     */
    public function getData()
    {
        $data = [];
        // --- case `wpapicase`
        self::getDataWPAPICASE($data);

        // --- case `tlapicase`
        self::getDataTLAPICASE($data);

        // --- case `migrationcase`
        self::getDataMIGRATIONCASE($data);

        // --- case `registrarrenewalcostpricezerocase`
        self::getDataREGISTRARRENEWALCOSTPRICEZEROCASE($data);

        // --- case `domain2premiumcase`
        self::getDataDOMAIN2PREMIUMCASE($data);

        return $data;
    }

    private static function generateOutputJS()
    {
        return <<<EOF
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
$('#statusbtn').off().on('click', function (event) {
    WHMCS.http.jqClient.post(
        WHMCS.adminUtils.getAdminRouteUrl('/widget/refresh&widget=IspapiMonitoringWidget&status=' + encodeURIComponent($(this).attr("value"))),
        function () {
            refreshWidget('IspapiMonitoringWidget', 'refresh=1')
        },
        'json'
    )
})
</script>
EOF;
    }

    /**
     * generate widget's html output
     * @param array $data input data (from getData method)
     * @return string html code
     */
    public function generateOutput($data)
    {
        $status = App::getFromRequest('status');
        if ($status !== "") {
            $setting = \WHMCS\Config\Setting::setValue("ispapiMonitoringWidget", $status);
            $success = $setting::getValue("ispapiMonitoringWidget") === $status;
            return [
                "success" => $success,
                "msg" => ("Widget status " . (($success) ? "changed" : "not changed"))
            ];
        }

        $status = \WHMCS\Config\Setting::getValue("ispapiMonitoringWidget");
        $jscript = self::generateOutputJS();

        if (empty($status)) { // null or 0
            return <<<EOF
<div class="widget-content-padded ispapi-monitoring-items">
    <button id="statusbtn" type="button" value="1" class="btn btn-primary btn-sm">Enable Widget</button>
</div>
{$jscript}
EOF;
        }
        $output = '<button id="statusbtn" type="button" value="0" class="btn btn-primary btn-sm">Disable Widget</button>';
        $case = App::getFromRequest('fixit');
        $item = App::getFromRequest('item');
        if ($case && $item) {
            return self::fixCase($case, $item);
        }
        if (empty($data)) {
            return self::returnOk("No issues detected.", $output, $jscript);
        }
        $output .= "<br/><br/>";
        foreach ($data as $key => $rows) {
            $output .= self::getCaseBlock($key, $rows);
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
{$jscript}
EOF;
    }
}
