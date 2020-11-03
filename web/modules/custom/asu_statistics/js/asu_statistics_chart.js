/**
@file
Javascript that renders a Chart.js chart.
*/

(function (Drupal, $) {
  "use strict";

  $(window).on('load', function(){
    $('.islandora-repository-reports-is-loading-message').fadeOut('slow')
  });

  $("#islandora_repository_reports_report_type").change(function(){
    $('#islandora-repository-reports-content').find('*').fadeTo(0, 0.3)
  });

  var IslandoraRepositoryReportsChartCanvas = document.getElementById('islandora-repository-reports-chart');
  var IslandoraRepositoryReportsChartType = drupalSettings.islandora_repository_reports.chart_type;

  if (IslandoraRepositoryReportsChartType == 'pie' || IslandoraRepositoryReportsChartType == 'doughnut') {
    var IslandoraRepositoryReportsPieChartData = drupalSettings.islandora_repository_reports.chart_data;
    var IslandoraRepositoryReportsPieChartTitle = drupalSettings.islandora_repository_reports.chart_title;
    if (IslandoraRepositoryReportsPieChartData != null) {
      var IslandoraRepositoryReportsPieChart = new Chart(IslandoraRepositoryReportsChartCanvas, {
        type: IslandoraRepositoryReportsChartType,
        data: IslandoraRepositoryReportsPieChartData,
        options: {
          layout: {
            padding: {
              top: 50,
              bottom: 100,
            }
          },
          title: {
            display: true,
            fontSize: 16,
	    text: [IslandoraRepositoryReportsPieChartTitle]
          }
        }
      });
    }
  }

  if (IslandoraRepositoryReportsChartType == 'bar') {
    var IslandoraRepositoryReportsBarChartData = drupalSettings.islandora_repository_reports.chart_data;
    var IslandoraRepositoryReportsBarChartTitle = drupalSettings.islandora_repository_reports.chart_title;
    if (IslandoraRepositoryReportsBarChartData != null) {
      var IslandoraRepositoryReportsBarChart = new Chart(IslandoraRepositoryReportsChartCanvas, {
        type: 'bar',
        data: IslandoraRepositoryReportsBarChartData,
        options: {
          layout: {
            padding: {
              top: 50,
              bottom: 100,
            }
          },
          title: {
            display: true,
            fontSize: 16,
	    text: IslandoraRepositoryReportsBarChartTitle
          },
          scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepSize: 1,
                }
            }]
          }
        }
      });
    }
  }

})(Drupal, jQuery);
