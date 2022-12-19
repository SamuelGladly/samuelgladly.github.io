﻿<%@ Page Language="C#" AutoEventWireup="true" CodeFile="EndUserSearchFile.aspx.cs" Inherits="EndUserSearchFile" %>

<!DOCTYPE html>
<html lang="zxx">

<head>
	<title>Auditing Data</title>
	<!-- Meta tag Keywords -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="UTF-8" />
	<meta name="keywords" content="Cakes Bakery Services Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template, Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
	<script>
		addEventListener("load", function () {
			setTimeout(hideURLbar, 0);
		}, false);

		function hideURLbar() {
			window.scrollTo(0, 1);
		}
	</script>
	<!-- //Meta tag Keywords -->

	<!-- Custom-Files -->
	<link rel="stylesheet" href="css/bootstrap.css">
	<!-- Bootstrap-Core-CSS -->
	<link rel="stylesheet" href="css/style.css" type="text/css" media="all" />
	<!-- Style-CSS -->
	<link rel="stylesheet" href="css/fontawesome-all.css">
	<!-- Font-Awesome-Icons-CSS -->
	<!-- //Custom-Files -->

	<!-- Web-Fonts -->
	<link href="//fonts.googleapis.com/css?family=Oxygen:300,400,700&amp;subset=latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese"
	    rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Pacifico&amp;subset=cyrillic,latin-ext,vietnamese" rel="stylesheet">
	<!-- //Web-Fonts -->

</head>

<body>
	<div class="mian-content">
		<!-- header -->
		<header>
			<nav class="navbar navbar-expand-lg navbar-light">
				<div class="logo text-left">
					<h1>
						<a class="navbar-brand" href="">
							Auditing Data</a>
					</h1>
				</div>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
				    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon">

					</span>
				</button>
				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<ul class="navbar-nav ml-lg-auto text-lg-right text-center">

						<li class="nav-item ">
							<a class="nav-link" href="EndUserHome.aspx">HOME
								<span class="sr-only">(current)</span>
							</a>
						</li>
						<li class="nav-item active">
							<a class="nav-link" href="EndUserSearchFile.aspx">SEARCH FILE</a>
						</li>
                        <li class="nav-item">
							<a class="nav-link" href="EndUserDownloadFile.aspx">DOWNLOAD FILE</a>
						</li>
                        <li class="nav-item">
							<a class="nav-link" href="Home.aspx">LOGOUT</a>
						</li>
                       
						
                       						
					</ul>
					<!-- menu button -->
					<div class="menu">
						<a href="#" class="navicon"></a>
						<div class="toggle">
							<ul class="toggle-menu list-unstyled">
								<li>
									<a href="EndUserFileKey.aspx">File Key</a>
								</li>
								
								<li>
									<a href="Home.aspx">Home</a>
								</li>
								
							</ul>
						</div>
					</div>
					<!-- //menu button -->
				</div>
			</nav>
		</header>
		<!-- //header -->

		<!-- banner 2 -->
		<div class="banner2-w3ls">

		</div>
		<!-- //banner 2 -->
	</div>
	<!-- main -->
	<!-- page details -->
	<div class="breadcrumb-agile">
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb m-0">
				<li class="breadcrumb-item">
					<a href="">EndUser</a>
				</li>
				<li class="breadcrumb-item active" aria-current="page">File Details</li>
			</ol>
		</nav>
	</div>
	<!-- //page details -->

	<!-- contact page -->
	<div class="address py-5">
		<div class="container py-xl-5 py-lg-3">
			
			<div  align="center">
				

				<div >
					<div class="address-grid">
						<h4 class="font-weight-bold mb-3">File Details</h4>
						<form action="#" method="post" runat="server">
                            <table>
                                <tr>
                                   <div class="form-group">
                                   <asp:TextBox ID="TextBox1" runat="server"  class="form-control" placeholder="" required="" Height="50" Width="400" style="text-align: center" ForeColor="#0000CC" Font-Bold="True" Font-Italic="True" ReadOnly="true"></asp:TextBox>	
                                   </div>
                                </tr>

                                <tr>
                                   <div class="form-group">
                                   <asp:TextBox ID="TextBox2" runat="server"  class="form-control" placeholder="" required="" Height="50" Width="400" style="text-align: center" ForeColor="#0000CC" Font-Bold="True" Font-Italic="True" ReadOnly="true"></asp:TextBox>	
                                   </div>
                                </tr>

                                                              
                                <tr>
                                   <div class="form-group">
                                       <asp:DropDownList ID="DropDownList1" runat="server" class="form-control" placeholder="" required="" Height="50" Width="400" style="text-align: center" Font-Italic="True" ForeColor="#003300"></asp:DropDownList>	
                                   </div>
                                </tr>
                               

                                <tr>
                                    <asp:Button ID="Button1" runat="server" Text="Submit" Height="50" Width="400" OnClick="Button1_Click" />
                                </tr>
                            </table><br /><br />
                            <asp:Panel ID="Panel1" runat="server" Visible="false">
                            <table>
                                <tr>
                                    <td>
                                        <div class="form-group">
                                            <asp:Label ID="Label1" runat="server" Text="Client Name"  Height="50" Width="200" ForeColor="#003300" Font-Bold="True" Font-Italic="True"></asp:Label>
                                   
                                        </div>
                                    </td> 
                                    <td>
                                        <div class="form-group">
                                            <asp:Label ID="Label2" runat="server" Text="Label"  Height="50" Width="200" ForeColor="#660066" Font-Bold="True" Font-Italic="True" ></asp:Label>
                                        </div>
                                    </td>
                                   
                                </tr>
                                 <tr>
                                    <td>
                                        <div class="form-group">
                                            <asp:Label ID="Label3" runat="server" Text="File Name"  Height="50" Width="200" ForeColor="#003300" Font-Bold="True" Font-Italic="True"></asp:Label>
                                   
                                        </div>
                                    </td> 
                                    <td>
                                        <div class="form-group">
                                            <asp:Label ID="Label4" runat="server" Text="Label"  Height="50" Width="200" ForeColor="#660066" Font-Bold="True" Font-Italic="True" ></asp:Label>
                                        </div>
                                    </td>
                                   
                                </tr>
                                 <tr>
                                    <td>
                                        <div class="form-group">
                                            <asp:Label ID="Label5" runat="server" Text="File"  Height="50" Width="200" ForeColor="#003300" Font-Bold="True" Font-Italic="True"></asp:Label>
                                   
                                        </div>
                                    </td> 
                                    <td>
                                        <div class="form-group">
                                            <asp:Label ID="Label6" runat="server" Text="Label"  Height="50" Width="200" ForeColor="#660066" Font-Bold="True" Font-Italic="True" ></asp:Label>
                                        </div>
                                    </td>
                                   
                                </tr>
                                 <tr>
                                    <td>
                                        <div class="form-group">
                                            <asp:Label ID="Label7" runat="server" Text="Status"  Height="50" Width="200" ForeColor="#003300" Font-Bold="True" Font-Italic="True"></asp:Label>
                                   
                                        </div>
                                    </td> 
                                    <td>
                                        <div class="form-group">
                                            <asp:Label ID="Label8" runat="server" Text="Label"  Height="50" Width="200" ForeColor="#660066" Font-Bold="True" Font-Italic="True" ></asp:Label>
                                        </div>
                                    </td>
                                   
                                </tr>
                                <tr>
                                    <td>
                                        <asp:Button ID="Button3" runat="server" Text="Send Request" Height="50" Width="200" OnClick="Button3_Click" />
                                    </td>
                                    <td>
                                        <asp:Button ID="Button4" runat="server" Text="Close" Height="50" Width="200" OnClick="Button4_Click" />
                                    </td>
                                </tr>

                            </table>
							</asp:Panel>
							                          
							
						</form>
					</div>
				</div>


			</div>
		</div>
	</div>
	<!-- map -->
	
	<!--// map -->
	<!-- //contact page -->


	<!-- footer -->
	<footer class="text-center py-sm-4 py-3">
		<div class="container py-xl-5 py-3">
			<div class="w3l-footer footer-social-agile mb-4">
				<ul class="list-unstyled">
					<li>
						<a href="#">
							<i class="fab fa-facebook-f"></i>
						</a>
					</li>
					<li class="mx-1">
						<a href="#">
							<i class="fab fa-twitter"></i>
						</a>
					</li>
					<li>
						<a href="#">
							<i class="fab fa-dribbble"></i>
						</a>
					</li>
					<li class="ml-1">
						<a href="#">
							<i class="fab fa-vk"></i>
						</a>
					</li>
				</ul>
			</div>
			<!-- copyright -->
			<p class="copy-right-grids text-light my-lg-5 my-4 pb-4">© Cloud Audit Data. All Rights Reserved 
				
			</p>
			<!-- //copyright -->
		</div>
		<!-- chef -->
		<img src="images/chef.png" alt="" class="img-fluid chef-style" />
		<!-- //chef -->
	</footer>
	<!-- //footer -->


	<!-- Js files -->
	<!-- JavaScript -->
	<script src="js/jquery-2.2.3.min.js"></script>
	<!-- Default-JavaScript-File -->

	<!-- menu-js -->
	<script>
		$('.navicon').on('click', function (e) {
			e.preventDefault();
			$(this).toggleClass('navicon--active');
			$('.toggle').toggleClass('toggle--active');
		});
	</script>
	<!-- //menu-js -->

	<!-- smooth scrolling -->
	<script src="js/SmoothScroll.min.js"></script>
	<!-- move-top -->
	<script src="js/move-top.js"></script>
	<!-- easing -->
	<script src="js/easing.js"></script>
	<!--  necessary snippets for few javascript files -->
	<script src="js/cakes-bakery.js"></script>

	<script src="js/bootstrap.js"></script>
	<!-- Necessary-JavaScript-File-For-Bootstrap -->

	<!-- //Js files -->

</body>

</html>


