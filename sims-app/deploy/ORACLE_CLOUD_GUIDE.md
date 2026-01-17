# Oracle Cloud Free Tier VPS Setup Guide

This guide will walk you through setting up a powerful **Always Free** VPS on Oracle Cloud to host your SIMS application and WhatsApp service.

## Step 1: Sign Up & Login
1.  Go to [Oracle Cloud Free Tier](https://www.oracle.com/cloud/free/).
2.  Click **Start for free**.
3.  Fill in your details.
    *   **Home Region**: Choose a region close to you (e.g., Dubai, Jeddah, Mumbai, Frankfurt). **Important**: You cannot change this later.
4.  Verify your email and identity (requires a credit card for verification, usually charges ~$1 and reverses it).

## Step 2: Create a VM Instance
Once logged into the Oracle Cloud Console:
1.  Click the **Hamburger Menu** (top left) -> **Compute** -> **Instances**.
2.  Click **Create Instance**.
3.  **Name**: Give it a name (e.g., `sims-server`).
4.  **Image and Shape**:
    *   Click **Edit**.
    *   **Image**: Choose **Canonical Ubuntu** (Select version **22.04** or **24.04**).
    *   **Shape**: Click **Change Shape**.
        *   Select **Ampere** (ARM) series.
        *   Checkbox **Ampere A1 Compute**.
        *   Configure OCPUs to **4** and Memory to **24 GB**. (This is the maximum free tier limit).
    *   Click **Select Shape**.
5.  **Networking**:
    *   Leave defaults (Create new virtual cloud network).
    *   Ensure **Assign a public IPv4 address** is selected.
6.  **Add SSH Keys**:
    *   Select **Save private key**. **DOWNLOAD THIS KEY AND KEEP IT SAFE!** You will need it to login.
    *   (Optional) If you have your own public key, you can upload it.
7.  **Boot Volume**:
    *   Default is 50GB. You can increase this up to 200GB for free if you want.
8.  Click **Create**.

## Step 3: Open Ports (Ingress Rules)
By default, Oracle blocks most ports. You need to open HTTP (80) and HTTPS (443).

1.  On the Instance details page, click the link under **Subnet** (e.g., `subnet-202X...`).
2.  Click the **Default Security List** for your VCN.
3.  Click **Add Ingress Rules**.
4.  Add the following rule:
    *   **Source CIDR**: `0.0.0.0/0`
    *   **IP Protocol**: TCP
    *   **Destination Port Range**: `80,443`
    *   **Description**: Allow HTTP and HTTPS
5.  Click **Add Ingress Rules**.

## Step 4: Connect to Your VPS
1.  Locate your instance's **Public IP Address** on the instance details page.
2.  Open your terminal (on your local PC).
3.  Move the key you downloaded to a safe place (e.g., `~/.ssh/oracle.key`).
4.  Set permissions: `chmod 600 ~/.ssh/oracle.key`.
5.  Connect:
    ```bash
    ssh -i ~/.ssh/oracle.key ubuntu@YOUR_PUBLIC_IP
    ```

## Step 5: Prepare for Deployment
Once connected to the server, run these commands to update the system:

```bash
sudo apt update
sudo apt upgrade -y
```

Now your server is ready for the deployment scripts we will create next!

## Summary of Server Specs
-   **OS**: Ubuntu 22.04/24.04
-   **CPU**: 4 Cores (Ampere ARM)
-   **RAM**: 24 GB
-   **Storage**: 50-200 GB
-   **Cost**: $0.00 / month
