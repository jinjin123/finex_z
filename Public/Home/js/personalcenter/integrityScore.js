/**
 * 积分明细
 * @param {页码} pager 
 * @param {*} dataType 
 */
function getIntegrityScoreData(pager, dataType) {
    $.get(getIntegrityScoreDataUrl, { 'dataType': dataType, 'p': pager }, function(result) {
        var html = "";
        for (var i = 0; i < result.data.length; i++) {
            html += '<tr>';
            html += '<td>' + result.data[i].add_time + '</td>';
            html += '<td>' + result.data[i].info + '</td>';
            html += '<td>' + result.data[i].pointadd + '</td>';
            html += '</tr>';
        }
        $('#dataTable').html(html);
        $('#dataPage').html(result.page);
    });
}