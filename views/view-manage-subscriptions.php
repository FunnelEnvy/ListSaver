<div class="wrap">
<h2>Manage Subscriptions</h2>
<br>	
<div class="container-fluid">	

<?php if( $_GET['tab'] == 'list_saver_pending_subscriptions' && $all_subscriptions): ?>
 <div class='rows' style="height:38px"><div class='col-sm-11'>
 <form method="post" action="" style="float:right">
  <input type="hidden" name="pending_subscriber" value="send">
  <input type="submit" value="Send Email" class="button-primary">
 </form>
</div></div>
<?php endif; ?>	
<?php if( $_GET['tab'] == 'list_saver_active_subscriptions' ): ?>
 <div class='rows' style="height:38px"><div class='col-sm-11'>
 <form method="post" action="" style="float:right">
  <input type="hidden" name="delete_subscriber" value="delete">
  <input type="submit" value="Delete Completed Subscribers" class="button-primary">
 </form>
</div></div>
<?php endif; ?>	
<div class='rows'><div class='col-sm-11'>
<div id="rules">

				<table class="table table-hover table-striped">
					<tr class="active">
						<th>First Name</th>
						<th>Last Name</th>
						<th>Email</th>
						<th>IP Address</th>
						<th>Date</th>
						<th>Status</th>
						<th>Email Sent</th>
					</tr>
					<?php
					if(isset($all_subscriptions))
					{
						foreach($all_subscriptions as $subscribe)
						{
						
						?>	
					<tr>
						<td>
							<?php echo $subscribe->sub_first_name; ?>
						</td>
						<td>
							<?php echo $subscribe->sub_last_name; ?>
						</td>
			
						<td>
							<?php echo $subscribe->sub_email; ?>
						</td>
						<td><?php echo $subscribe->sub_ip; ?></td>
						<td><?php echo date('F,d Y',strtotime($subscribe->sub_date)); ?></td>
						
						<td><?php
						    
							if($subscribe->sub_status == 'a')
							{
								$status =  'Active';
							}
							else
							{
								$status = 'Pending';
							}
							echo $status;
								
							?></td>
							<td><?php
						    
							if($subscribe->sub_email_sent == 'true')
							{
								$status =  'YES';
							}
							else
							{
								$status = 'NO';
							}
							echo $status;
								
							?></td>
					</tr>
					<?php
				}
			}
			else
			{
					echo "<tr><td align='center' colspan='5'>Currently no subscriptions added. </td></tr>";
			}
			?>
				</table>
		    </div>
</div>
</div>
</div></div> 
