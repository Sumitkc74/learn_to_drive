<div class="ltd-panel ltd-chart-panel">
    <h2 class="ltd-panel__title">User Signups (Last 6 Months)</h2>
    <canvas id="ltdSignupsChart" height="90"></canvas>
</div>
<script>
(function () {
    var canvas = document.getElementById('ltdSignupsChart');
    if (!canvas || typeof Chart === 'undefined') return;
    new Chart(canvas.getContext('2d'), {type:'line',data:{labels:@json($signupLabels),datasets:[{label:'New Users',data:@json($signupCounts),borderColor:'#FFDE17',backgroundColor:'rgba(255,222,23,.15)',borderWidth:2,pointBackgroundColor:'#3B3B3B',pointRadius:4,tension:.35,fill:true}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false}}}}});
})();
</script>
