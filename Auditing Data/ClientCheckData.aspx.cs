using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;
using System.Security.Cryptography;
using System.IO.Compression;
using System.IO;

public partial class ClientCheckData : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {
        if (Session["ClientMailID"] != null && Session["ClientName"] != null)
        {
            TextBox1.Text = Session["ClientMailID"].ToString();
            TextBox2.Text = Session["ClientName"].ToString();
        }
        if(!Page.IsPostBack)
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
        cmd = new SqlCommand("select FileName from FileUpload where ClientMailID='"+TextBox1.Text+"'",con);
        da = new SqlDataAdapter(cmd);
        da.Fill(dt);
        DropDownList1.DataSource = dt;
        DropDownList1.DataBind();
        DropDownList1.DataTextField = "FileName";
      //  DropDownList1.DataValueField = "FileName";
        DropDownList1.DataBind();
        con.Close();
    }

    private void check()
    {
        con2.Open();
        cmd2 = new SqlCommand("select * from CheckData where FileName='" + DropDownList1.Text + "'", con2);
        dr2 = cmd2.ExecuteReader();
        if (dr2.Read())
        {
            Response.Write("<script>alert('File is Attacked by Attacker...!')</script>");

            con.Open();
            cmd = new SqlCommand("delete from CheckData where FileName='" + DropDownList1.Text + "'", con);
            cmd.ExecuteNonQuery();
            con.Close();

            EncryptFile();

            con4.Open();
            cmd4 = new SqlCommand("insert into AuditDetails values('" + DropDownList1.Text + "','File Recovered','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
            cmd4.ExecuteNonQuery();
            con4.Close();

            Response.Write("<script>alert('File is Recovered...!')</script>");

        }
        else
        {
            
            Response.Write("<script>alert('Your File is Secured')</script>");
        }
        con2.Close();
    }

    private void EncryptFile()
    {

        //Build the File Path for the original (input) and the encrypted (output) file.
        string input = Server.MapPath("~/Files/") + DropDownList1.Text + ".txt";
        string output = Server.MapPath("~/FilesE/") + DropDownList1.Text + ".txt";

        //Save the Input File, Encrypt it and save the encrypted file in output path.
       
        this.Encrypt(input, output);

    }

    private void Encrypt(string inputFilePath, string outputfilePath)
    {
        string EncryptionKey = "MAKV2SPBNI99212";
        using (Aes encryptor = Aes.Create())
        {
            Rfc2898DeriveBytes pdb = new Rfc2898DeriveBytes(EncryptionKey, new byte[] { 0x49, 0x76, 0x61, 0x6e, 0x20, 0x4d, 0x65, 0x64, 0x76, 0x65, 0x64, 0x65, 0x76 });
            encryptor.Key = pdb.GetBytes(32);
            encryptor.IV = pdb.GetBytes(16);
            using (FileStream fsOutput = new FileStream(outputfilePath, FileMode.Create))
            {
                using (CryptoStream cs = new CryptoStream(fsOutput, encryptor.CreateEncryptor(), CryptoStreamMode.Write))
                {
                    using (FileStream fsInput = new FileStream(inputFilePath, FileMode.Open))
                    {
                        int data;
                        while ((data = fsInput.ReadByte()) != -1)
                        {
                            cs.WriteByte((byte)data);
                        }
                    }
                }
            }
        }
    }

    protected void Button1_Click(object sender, EventArgs e)
    {
        check();
        
    }
}