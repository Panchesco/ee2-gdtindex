<section>
<?php $i=1; foreach($grid as $row) { ?>
	<article>
		<div class="row">
			<div class="twelve columns">
				<h4><?php echo $row->title //preg_replace("/(Components|Systems) [0-9]{0,} |_|\.pdf/i"," ",$row->title);?></h4>
			</div>
				<div class="three columns">
				<img src="/img/square.png">
			</div>
				<ul class="four columns">
					<li><?php echo $row->division;?></li>
					<li><?php echo $row->power;?>W</li>
					<li><?php echo $row->wavelength;?>nm</li>
					<li><?php echo $row->category;?></li>
					<li><?php echo ($row->series != '--') ? $row->series : '';?></li>
				</ul>
				<ul class="four columns">
					<li><?php echo $row->beam_delivery_method;?></li>
					<?php if($row->fiber_core) { ?><li><?php echo $row->fiber_core;?> Fiber Core</li><?php } ?>
					<li><?php echo str_replace('|',', ',$row->cooling);?></li>
					<li><?php echo $row->operation_mode;?> Operation Mode</li>
					<?php if($row->application) { ?><li><?php echo $row->application;?></li><?php } ?>
				</ul>
		</div><!-- /.row -->
		<hr>
		<?php if(count($grid)==0) { ?>
		<?php echo $no_results ;?>
		<?php } ?>		
	</article>
<?php $i++; } ?>
</section>
