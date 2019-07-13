<?php

/**
 * Statistics representation.
 * @author matti
 *
 */
class StatsView extends AbstractView {
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController ( $ctrl );
	}
	
	protected function statsBlock($title, $chartId) {
		?>
		<div class="statsTile">
			<div class="statsTileTitle"><?php echo $title; ?></div>
			<div class="statsChart" id="statsChart<?php echo $chartId; ?>">
			</div>
			<script>
			<?php
			$func = strtolower($chartId) . "Chart";
			$this->$func();
			?>
			</script>
		</div>
		<?php
	}
	
	protected function rehearsalChart() {
		$data = $this->getData()->last6MonthsNumberEvents("rehearsal");
		?>
		$(document).ready(function() {
			var series = [<?php echo json_encode($data["series"]); ?>];
			var labels = <?php echo json_encode($data["labels"]); ?>;
			
			$.jqplot.config.enablePlugins = true;
			rehearsalPlot = $.jqplot('statsChartRehearsal', series, {
				seriesColors:['#436f98'],
				animate: !$.jqplot.use_excanvas,
	            seriesDefaults:{
	                pointLabels: { show: true }
	            },
	            grid: {
	            	background: '#ffffff',
	            	borderWidth: 1,
	            	borderColor: '#dfdfdf',
	            	shadow: false
	            },
	            axes: {
	                xaxis: {
	                    renderer: $.jqplot.CategoryAxisRenderer,
	                    ticks: labels
	                },
	                yaxis: {
	                	min: 0
	                }
	            },
	            highlighter: { show: false }
			});
		});
		<?php
	}
	
	protected function concertChart() {
		$data = $this->getData()->last6MonthsNumberEvents("concert");
		?>
		$(document).ready(function() {
			var series = [<?php echo json_encode($data["series"]); ?>];
			var labels = <?php echo json_encode($data["labels"]); ?>;
			
			$.jqplot.config.enablePlugins = true;
			rehearsalPlot = $.jqplot('statsChartConcert', series, {
				seriesColors:['#436f98'],
				animate: !$.jqplot.use_excanvas,
	            seriesDefaults:{
	                pointLabels: { show: true }
	            },
	            grid: {
	            	background: '#ffffff',
	            	borderWidth: 1,
	            	borderColor: '#dfdfdf',
	            	shadow: false
	            },
	            axes: {
	                xaxis: {
	                    renderer: $.jqplot.CategoryAxisRenderer,
	                    ticks: labels
	                },
	                yaxis: {
	                	min: 0
	                }
	            },
	            highlighter: { show: false }
			});
		});	
		<?php
	}
	
	protected function memberChart() {
		$data = $this->getData()->membersPerGroup();
		?>
		$(document).ready(function() {
			var series = [<?php echo json_encode($data["series"]); ?>];
			var labels = <?php echo json_encode($data["labels"]); ?>;
			
			$.jqplot.config.enablePlugins = true;
			rehearsalPlot = $.jqplot('statsChartMember', series, {
				seriesColors:['#436f98'],
				animate: !$.jqplot.use_excanvas,
	            seriesDefaults:{
	                renderer: $.jqplot.BarRenderer,
	                pointLabels: { show: true }
	            },
	            grid: {
	            	background: '#ffffff',
	            	borderWidth: 1,
	            	borderColor: '#dfdfdf',
	            	shadow: false
	            },
	            axes: {
	                xaxis: {
	                    renderer: $.jqplot.CategoryAxisRenderer,
	                    ticks: labels
	                }
	            },
	            highlighter: { show: false }
			});
		});	
		<?php
	}
	
	protected function memberrehearsalperformanceChart() {
		?>
		$(document).ready(function() { $('#statsChartMemberRehearsalPerformance').hide(); });
		</script>
		<?php
		$data = $this->getData()->memberRehearsalPerformance();
		$table = new Table($data);
		$table->showFilter(false);
		$table->renameHeader("surname", Lang::txt("StatsView_memberrehearsalperformanceChart.surname"));
		$table->renameHeader("name", Lang::txt("StatsView_memberrehearsalperformanceChart.name"));
		$table->renameHeader("score", Lang::txt("StatsView_memberrehearsalperformanceChart.score"));
		$table->renameHeader("rank", Lang::txt("StatsView_memberrehearsalperformanceChart.rank"));
		$table->write();
		echo "<script>";
	}
	
	protected function membervoteperformanceChart() {
		?>
		$(document).ready(function() { $('#statsChartMemberVotePerformance').hide(); });
		</script>
		<?php
		$data = $this->getData()->memberVotePerformance();
		$table = new Table($data);
		$table->showFilter(false);
		$table->renameHeader("surname", Lang::txt("StatsView_membervoteperformanceChart.surname"));
		$table->renameHeader("name", Lang::txt("StatsView_membervoteperformanceChart.name"));
		$table->renameHeader("score", Lang::txt("StatsView_membervoteperformanceChart.score"));
		$table->renameHeader("rank", Lang::txt("StatsView_membervoteperformanceChart.rank"));
		$table->write();
		echo "<script>";
	}
		
	protected function memberoptionperformanceChart() {
		?>
		$(document).ready(function() { $('#statsChartMemberOptionPerformance').hide(); });
		</script>
		<?php
		$data = $this->getData()->memberOptionPerformance();
		$table = new Table($data);
		$table->showFilter(false);
		$table->renameHeader("surname", Lang::txt("StatsView_memberoptionperformanceChart.surname"));
		$table->renameHeader("name", Lang::txt("StatsView_memberoptionperformanceChart.name"));
		$table->renameHeader("score", Lang::txt("StatsView_memberoptionperformanceChart.score"));
		$table->renameHeader("rank", Lang::txt("StatsView_memberoptionperformanceChart.rank"));
		$table->write();
		echo "<script>";
	}
	
	function start() {
		?>
		<script type="text/javascript" src="lib/jquery/plugins/jqplot.barRenderer.min.js"></script>
		<script type="text/javascript" src="lib/jquery/plugins/jqplot.categoryAxisRenderer.min.js"></script>
		<script type="text/javascript" src="lib/jquery/plugins/jqplot.pointLabels.min.js"></script>
		<?php
		$this->statsBlock(Lang::txt("StatsView_start.Rehearsal_title"), "Rehearsal");
		$this->statsBlock(Lang::txt("StatsView_start.concertstat_title"), "Concert");
		$this->statsBlock(Lang::txt("StatsView_start.memberstat_title"), "Member");
		$this->statsBlock(Lang::txt("StatsView_start.memberrehearsalperformancestat_title"), "MemberRehearsalPerformance");
		$this->statsBlock(Lang::txt("StatsView_start.membervoteperformancestat_title"), "MemberVotePerformance");
		$this->statsBlock(Lang::txt("StatsView_start.memberoptionperformancestat_title"), "MemberOptionPerformance");
	}
	
	function startOptions() {
		// none
	}
}

?>