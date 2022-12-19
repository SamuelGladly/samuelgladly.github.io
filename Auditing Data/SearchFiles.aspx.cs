using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;
using System.IO;

public partial class SearchFiles : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {
        if (!Page.IsPostBack)
        {
            dl();
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
    private void dl()
    {
        con.Open();
        cmd = new SqlCommand("select FileName from FileUpload", con);
        da = new SqlDataAdapter(cmd);
        da.Fill(dt);
        DropDownList1.DataSource = dt;
        DropDownList1.DataBind();
        DropDownList1.DataTextField = "FileName";
        //  DropDownList1.DataValueField = "FileName";
        DropDownList1.DataBind();
        con.Close();
    }

    protected void Button1_Click(object sender, EventArgs e)
    {
        StreamReader sr = new StreamReader(Server.MapPath("~/FilesE/" + DropDownList1.Text + ".txt"));
        string read = sr.ReadToEnd();
        TextBox1.Text = read;
        sr.Close();
    }

    protected void Button2_Click(object sender, EventArgs e)
    {
        string filePath = Server.MapPath("~/FilesE/" + DropDownList1.Text + ".txt");

        FileInfo file = new FileInfo(filePath);
        if (file.Exists)//check file exsit or not
        {
            file.Delete();

        }
        FileStream fStream = new FileStream(filePath, FileMode.Create, FileAccess.Write);
        StreamWriter sWriter = new StreamWriter(fStream);
        sWriter.WriteLine(TextBox1.Text);

        sWriter.Close();
        fStream.Close();

        con4.Open();
        cmd4 = new SqlCommand("insert into AuditDetails values('" + DropDownList1.Text + "','File Hacked','Hacker','" + DateTime.Now.ToString() + "')", con4);
        cmd4.ExecuteNonQuery();
        con4.Close();

        con.Open();
        cmd = new SqlCommand("insert into CheckData values('" + DropDownList1.Text + "')", con);
        cmd.ExecuteNonQuery();
        con.Close();
    }
}