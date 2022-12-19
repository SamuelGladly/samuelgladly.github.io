using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;

public partial class ClientViewFiles : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {
        if (Session["ClientMailID"] != null && Session["ClientName"] != null)
        {
            TextBox1.Text = Session["ClientMailID"].ToString();
            TextBox2.Text = Session["ClientName"].ToString();
        }
    }

    SqlConnection con = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con1 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con2 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con3 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd;
    SqlCommand cmd2;
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

        string a = gvrow.Cells[0].Text;

        // string date = DateTime.Now.ToString("dd/MM/yyyy");
        con3.Open();
        cmd = new SqlCommand("update FileUpload set ClientStatus='Activated' where FileName='" + a + "'", con3);
        cmd.ExecuteNonQuery();
        con3.Close();
        Response.Write("<script>alert('Activated Successfully')</script>");
        Response.Redirect("ClientViewFiles.aspx");
        Response.Write("<script>alert('Activated Successfully')</script>");
    }

    protected void LinkButton2_Click(object sender, EventArgs e)
    {
        LinkButton lnkbtn = sender as LinkButton;
        GridViewRow gvrow = lnkbtn.NamingContainer as GridViewRow;

        string a = gvrow.Cells[0].Text;

        // string date = DateTime.Now.ToString("dd/MM/yyyy");
        con3.Open();
        cmd = new SqlCommand("update FileUpload set ClientStatus='Deactivated' where FileName='" + a + "'", con3);
        cmd.ExecuteNonQuery();
        con3.Close();
        Response.Write("<script>alert('Deactivated Successfully')</script>");
        Response.Redirect("ClientViewFiles.aspx");
        Response.Write("<script>alert('Deactivated Successfully')</script>");
    }
}