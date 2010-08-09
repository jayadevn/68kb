<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>{kb:site_title}</title>
{kb:head_data}
<meta name="keywords" content="{kb:site_keywords}" />
<meta name="description" content="{kb:site_keywords}" />
<base href="<?php echo base_url()?>" />
<link href="{kb:site_theme}/css/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
	
<div class="container_12">
	
	<div class="grid_6" id="logo">
		<h1><a href="http://68kb.com">Your Site</a></h1>
	</div>
	<div class="grid_6" id="login">
		<p>
			<a href="<?php echo site_url('users/login') ?>">Login</a> | <a href="<?php echo site_url('users/register') ?>">Register</a>
		</p>
	</div>
	<div class="clear"></div>
	
	<div class="grid_12 blue">
		<div id="slatenav">
			<ul>
				<li><a href="index.php" title="css menus" class="current">Home</a></li>
				<li><a href="<?php echo site_url('categories'); ?>" title="css menus">Categories</a></li>
				<li><a href="<?php echo site_url('glossary'); ?>" title="css menus">Glossary</a></li>
				<li><a href="<?php echo site_url('search'); ?>" title="css menus">Advanced Search</a></li>
				<li><a href="<?php echo site_url('contact'); ?>" title="css menus">Contact Us</a></li>
			</ul>
		</div>
	</div>
	<div class="clear"></div>
	
	<div class="grid_9 body">
		<?php echo $body; ?>
	</div>
	<div class="grid_3" id="sidebar">
		<div class="item">
			<h3>Categories</h3>
			{kb:categories:cat_list}
		</div>
	</div>
	<div class="clear"></div>
	
	<div class="grid_12 footer">
		<p>
			&copy; <?php echo date("Y"); ?> 68kb - v<?php echo $settings['script_version']; ?> - Build <?php echo $settings['script_build']; ?><br />
			Time: <?php echo $this->benchmark->elapsed_time();?> - Memory: <?php echo $this->benchmark->memory_usage();?>
		</p>
	</div>
</div>
</body>
</html>
