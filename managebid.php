<?php
	session_start();
	include('db.php');
	include('header.php');

	if(!isset($_SESSION['key']))
	{
		header("Location: /stuff-sharing/login.php?error=NOT_LOGIN");
	}
	else
	{
		$email = pg_escape_string($connection,$_SESSION['key']);
		$query = "SELECT firstName, lastName, userPoint FROM users where email='".$email."'";
		$result = pg_query($connection,$query) or die('Query failed:'.pg_last_error());
		$info = pg_fetch_row($result);
	}
	
	if(isset($_SESSION['biditemid']))
	{
		$biditemid = pg_escape_string($connection,$_SESSION['biditemid']);
		$itemresult = pg_query($connection,"SELECT l.itemName,l.itemId,l.itemCategory,l.itemDescription,a.minimumBidPoint,u.firstname,u.lastname,u.email 
		FROM ItemList l, Advertise a, Users u WHERE l.itemid = '".$biditemid."' and a.itemid = l.itemid and l.owneremail = u.email");  
		$biddersresult = pg_query($connection, "SELECT b.bidderId, b.bidAmount, u.firstname, u.lastname FROM BiddingList b, Users u WHERE b.itemId = '".$biditemid."' and u.email = b.bidderId");
		if(!$itemresult)
		{
			$message = "Something seems to be wrong, please try later";
		}		
	}
		
	if(isset($_POST['winnerid']))
	{
		$winnerid = pg_escape_string($connection,$_POST['winnerid']);
		$winpoint = pg_escape_string($connection,$_POST['winpoint']);
		$updatepoint = (int)$info[2] + (int)$winpoint;
		$updatepointquery = "update Users set userPoint = '".$updatepoint."' where email = '".$email."'";
		$updatepointresult = pg_query($connection,$updatepointquery);
		while($row = pg_fetch_row($biddersresult)){		
			$amount = (int)$row[1];
			$fetchpointquery = "select userpoint from users where email = '".$row[0]."'";
			$fetchpointresult = pg_query($connection,$fetchpointquery);
			if($fetchpointresult)
			{
				$pointrow = pg_fetch_row($fetchpointresult);
				$currentpoint = (int)$pointrow[0];
				$recoveredpoint = $amount + $currentpoint;
				$recoverpointquery = "update Users set userPoint = '" .$recoveredpoint."' where email = '".$row[0]."' and email <> '".$winnerid."'";
				$recoverpointresult = pg_query($connection,$recoverpointquery);
				if($recoverpointresult)
				{
					$deletebidquery = "DELETE FROM BiddingList WHERE bidderId = '".$row[0]."' AND itemId = '".$biditemid."'";
					$deletebidresult = pg_query($connection,$deletebidquery);
				}
			}
		}
		$deleteadquery = "DELETE FROM Advertise WHERE itemId = '".$biditemid."'";
		$deleteadresult = pg_query($connection,$deleteadquery);
		$addrecordquery = "insert into Record(itemId,bidderId,bidAmount) values ('"
  		. $biditemid ."','" . $winnerid ."', '" .$winpoint."')";
		$addrecordresult = pg_query($connection,$addrecordquery);	
		if(!($updatepointresult and $fetchpointresult and $recoverpointresult and $deletebidresult and $deleteadresult and $addrecordresult))
		{
			$message = "Something seems to be wrong, please try later";
		}
		else
		{
			header("Location: /stuff-sharing/welcome.php");			
		}
	}
	
	if(isset($_POST['dismissbid']))
	{
		while($row = pg_fetch_row($biddersresult)){		
			$amount = (int)$row[1];
			$fetchpointquery = "select userpoint from users where email = '".$row[0]."'";
			$fetchpointresult = pg_query($connection,$fetchpointquery);
			if($fetchpointresult)
			{
				$pointrow = pg_fetch_row($fetchpointresult);
				$currentpoint = (int)$pointrow[0];
				$recoveredpoint = $amount + $currentpoint;
				$recoverpointquery = "update Users set userPoint = '" .$recoveredpoint."' where email = '".$row[0]."'";
				$recoverpointresult = pg_query($connection,$recoverpointquery);
				if($recoverpointresult)
				{
					$deletebidquery = "DELETE FROM BiddingList WHERE bidderId = '".$row[0]."' AND itemId = '".$biditemid."'";
					$deletebidresult = pg_query($connection,$deletebidquery);
				}
			}
		}
		$deleteadquery = "DELETE FROM Advertise WHERE itemId = '".$biditemid."'";
		$deleteadresult = pg_query($connection,$deleteadquery);
		if(!($fetchpointresult && $recoverpointresult && $deletebidresult && $deleteadresult))
		{
			$message = "Something seems to be wrong, please try later";
		}
		else
		{
			header("Location: /stuff-sharing/welcome.php");
		}
	}
?>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
            <a class="navbar-brand" href="/stuff-sharing/welcome.php"><?php echo $info[0]. " " .$info[1] ?></a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
					<ul class="nav navbar-nav">
						<li><a href="myitem.php">My Items</a></li>
						<li><a href="additem.php">Add Item</a></li>
					</ul>
          <ul class="nav navbar-nav navbar-right">
						<li>User Point:<?php echo $info[2];?></li>
            <li><a href="/stuff-sharing/logout.php/">Logout</a></li>
          </ul> 
        </div>
      </div>
    </nav>

<div class="container">
  <div class="row">
    <div class="col-md-8 col-md-offset-2">
		  <div class="panel panel-default">
			  <div class="panel-heading">
          <h3 class="panel-title">Item Info</h3>
        </div>
				<div class="panel-body">				
				<?php
					if(isset($message))
					{
						echo '<div class="alert alert-danger" role="alert">
										<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
										<span class="sr-only">Error:</span>' . 
										$message .
									'</div>';
					}
        ?>
				<?php
				$row = pg_fetch_row($itemresult);
				echo "<p>Item Name: ".$row[0]."</p>";
				echo "<p>Item Id: ".$row[1]."</p>";
				echo "<p>Item Category: ".$row[2]."</p>";
				echo "<p>Item Description: ".$row[3]."</p>";
				echo "<p>Minimum Bidding Point: ".$row[4]."</p>";
				echo "<p>Owner's Name: ".$row[5]." ".$row[6]."</p>";
				echo "<p>Owner's Email ".$row[7]."</p>";
				?>
				</div>
			</div>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">Bidder List</h3>
        </div>
        <div class="panel-body">
		  <div class="table-responsive">
			<table class="table table-striped table-bordered table-list">
			<thead>
			  <tr>
			    <th>Bidder Name</th> <th>Bidder Email</th> <th>Bid Point</th> <th>Action</th>
			  </tr>
			</thead>
			<tbody>
			<?php
			while($row = pg_fetch_row($biddersresult)){
				echo "\t<tr>\n";
				echo "\t\t<td>".$row[2]." ".$row[3]."</td>\n";
				echo "\t\t<td>".$row[0]."</td>\n";
				echo "\t\t<td>".$row[1]."</td>\n";
				
				echo "\t\t<td>";
				echo "<form action=\"managebid.php\" method=\"post\">";
				echo "<input type=\"hidden\" name=\"winnerid\" value=\"".$row[0]."\"/>";
				echo "<input type=\"hidden\" name=\"winpoint\" value=\"".$row[1]."\"/>";
				echo "<button type=\"submit\" class=\"btn btn-success\" >accept bid</button></form>\n";
				echo "</td>\n";
				
				echo "\t</tr>\n";
			}
			?>
			</tbody>
		  </table>
		  </div>
	    </div>
		<div class="panel-footer">
		<?php
				echo "<form action=\"managebid.php\" method=\"post\">";
				echo "<input type=\"hidden\" name=\"dismissbid\"/>";
				echo "<button type=\"submit\" class=\"btn btn-danger\">close bid</button></form>\n";
		?>
		</div>
    </div>
    </div>
  </div>
</div>
</body>