function saveEvent(form) {
    var countryId = $(field).val();
    var targetSelect = "#" + $(field).data("populate");
    if (countryId > 0) {
        $(targetSelect).prop("disabled", true);
        $.ajax({
            url: "/index.php?type=113&no_cache=1&tx_plugin_jsondata[country]=" + countryId,
            dataType: "json",
            success: function(returnData) {
                if (returnData) {
                    $(targetSelect)
                        .find('option')
                        .remove();

                    $.each(returnData, function (index, item) {
                        $(targetSelect).append("<option value=\"" + item.uid + "\">" + item.localName + "</option>");
                        $(targetSelect).removeAttr("disabled");
                    });
                }
            }
        })
    } else {
        $(targetSelect).find("option").remove();
        $(targetSelect).append("<option value=\"\">Please select a country first</option>");
    }
}
