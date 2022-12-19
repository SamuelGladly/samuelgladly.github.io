using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;

public partial class ProxyFileUpload : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {

    }
    SqlConnection con = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con1 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con2 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con3 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd;
    SqlDataAdapter da;
    DataTable dt = new DataTable();
    SqlDataReader dr;
    SqlDataReader dr1;
    SqlDataReader dr2;
    DataSet ds = new DataSet();
    SqlConnection con4 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd4;

    protected void LinkButton1_Click(object sender, EventArgs e)
    {
        LinkButton lnkbtn = sender as LinkButton;
        GridViewRow gvrow = lnkbtn.NamingContainer as GridViewRow;

        string a = gvrow.Cells[1].Text;

        // string date = DateTime.Now.ToString("dd/MM/yyyy");
        con3.Open();
        cmd = new SqlCommand("update FileUpload set ProxyStatus='Activated' where FileName='" + a + "'", con3);
        cmd.ExecuteNonQuery();
        con3.Close();
        Response.Write("<script>alert('Activated Successfully')</script>");
        Response.Redirect("ProxyFileUpload.aspx");
        Response.Write("<script>alert('Activated Successfully')</script>");
    }

    protected void LinkButton2_Click(object sender, EventArgs e)
    {
        LinkButton lnkbtn = sender as LinkButton;
        GridViewRow gvrow = lnkbtn.NamingContainer as GridViewRow;

        string a = gvrow.Cells[1].Text;

        // string date = DateTime.Now.ToString("dd/MM/yyyy");
        con3.Open();
        cmd = new SqlCommand("update FileUpload set ProxyStatus='Deactivated' where FileName='" + a + "'", con3);
        cmd.ExecuteNonQuery();
        con3.Close();
        Response.Write("<script>alert('Deactivated Successfully')</script>");
        Response.Redirect("ProxyFileUpload.aspx");
        Response.Write("<script>alert('Deactivated Successfully')</script>");
    }
}