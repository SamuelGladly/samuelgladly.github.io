<%@ Page Language="C#" AutoEventWireup="true" CodeFile="ProxyFileUpload.aspx.cs" Inherits="ProxyFileUpload" %>

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

    <style type="text/css">
        .auto-style1 {
            width: 261px;
        }
    </style>

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
							<a class="nav-link" href="ProxyHome.aspx">HOME
								<span class="sr-only">(current)</span>
							</a>
						</li>
						<li class="nav-item ">
							<a class="nav-link" href="ProxyClientInformation.aspx">CLIENT INFORMATION</a>
						</li>
                        <li class="nav-item active">
							<a class="nav-link" href="ProxyFileUpload.aspx">AUTHORIZE FILE UPLOAD</a>
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
									<a href="ProxyUserInformation.aspx">User_Information</a>
								</li>
								<li>
									<a href="ProxyTransaction.aspx">View_Transaction</a>
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
					<a href="">Proxy</a>
				</li>
				<li class="breadcrumb-item active" aria-current="page">File Information</li>
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
						<h4 class="font-weight-bold mb-3">File Information</h4>
						<form id="form1" runat="server">
                            <table>
                                <tr>
                                   <div class="form-group">
                                       <asp:GridView ID="GridView1" runat="server" AutoGenerateColumns="False" Font-Size="15pt" BackColor="#DEBA84" BorderColor="#DEBA84" BorderStyle="None" BorderWidth="1px" CellPadding="3" CellSpacing="2" DataSourceID="SqlDataSource1">
                                           <Columns>
                                               <asp:BoundField DataField="ClientName" HeaderText="ClientName" SortExpression="ClientName" />
                                               <asp:BoundField DataField="FileName" HeaderText="FileName" SortExpression="FileName" />
                                               <asp:BoundField DataField="UploadFile" HeaderText="UploadFile" SortExpression="UploadFile" />
                                               <asp:BoundField DataField="Date" HeaderText="Date" SortExpression="Date" />
                                               <asp:BoundField DataField="ProxyStatus" HeaderText="ProxyStatus" SortExpression="ProxyStatus" />

                                                <asp:TemplateField HeaderText="Activate">
                                         <ItemTemplate>
                                              <asp:LinkButton ID="LinkButton1" runat="server" Text="Click" OnClick="LinkButton1_Click" ></asp:LinkButton>
                                         </ItemTemplate>
                                         </asp:TemplateField> 
                                                                    
                                         <asp:TemplateField HeaderText="Deactivate">
                                         <ItemTemplate>
                                              <asp:LinkButton ID="LinkButton2" runat="server" Text="Click" OnClick="LinkButton2_Click"></asp:LinkButton>
                                         </ItemTemplate>
                                         </asp:TemplateField>
                                           </Columns>
                                           <FooterStyle BackColor="#F7DFB5" ForeColor="#8C4510" />
                                           <HeaderStyle BackColor="#A55129" Font-Bold="True" ForeColor="White" />
                                           <PagerStyle ForeColor="#8C4510" HorizontalAlign="Center" />
                                           <RowStyle BackColor="#FFF7E7" ForeColor="#8C4510" />
                                           <SelectedRowStyle BackColor="#738A9C" Font-Bold="True" ForeColor="White" />
                                           <SortedAscendingCellStyle BackColor="#FFF1D4" />
                                           <SortedAscendingHeaderStyle BackColor="#B95C30" />
                                           <SortedDescendingCellStyle BackColor="#F1E5CE" />
                                           <SortedDescendingHeaderStyle BackColor="#93451F" />
                                          
                                    </asp:GridView>
							       
							        <asp:SqlDataSource ID="SqlDataSource1" runat="server" ConnectionString="<%$ ConnectionStrings:AuditDataConnectionString %>" SelectCommand="SELECT [ClientName], [FileName], [UploadFile], [Date], [ProxyStatus] FROM [FileUpload] ORDER BY [ID] DESC"></asp:SqlDataSource>
							       
							       </div>
                                </tr>
                                
                            </table>
							
							
                            
							
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
