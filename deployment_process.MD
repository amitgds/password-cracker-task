 Deploying PHP Application on AWS EC2 Using Terraform

This guide outlines the detailed step-by-step process for deploying a PHP-based password cracking API on a single AWS EC2 instance using Terraform. The deployment will be fully automated, including server provisioning, security setup, software installation, and deployment.

 1. Prerequisites

- AWS IAM credentials with admin privileges
- Terraform installed (`terraform -v` to check)
- AWS CLI configured (`aws configure`)

 2. Infrastructure Overview

- EC2 Instance → Runs PHP API and stores dictionary files
- IAM Role for EC2 → Grants permissions to EC2
- Security Groups → Allows required inbound/outbound traffic
- EBS Storage → Stores application files and dictionary files
- RDS Database → Manages database storage
- Secrets Manager → Stores database credentials securely
- Application Load Balancer (ALB) → Handles traffic to EC2

 3. Create IAM Role for EC2

EC2 needs an IAM role to access RDS, Secrets Manager, and CloudWatch logs.


resource "aws_iam_role" "ec2_role" {
  name = "EC2InstanceRole"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
      Action = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_instance_profile" "ec2_profile" {
  name = "EC2InstanceProfile"
  role = aws_iam_role.ec2_role.name
}


 4. Create Security Group for EC2


resource "aws_security_group" "ec2_sg" {
  name = "EC2SecurityGroup"

  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}


 5. Provision EC2 Instance with Terraform

 
 resource "aws_key_pair" "existing_key" {
  key_name   = "my-existing-key"
  public_key = "ssh-rsa ...your-public-key... user@your-machine"
}




resource "aws_instance" "php_server" {
  ami                    = "ami-0c55b159cbfafe1f0"
  instance_type          = "t3.medium"
  key_name               = aws_key_pair.existing_key.key_name
  security_groups        = [aws_security_group.ec2_sg.name]
  iam_instance_profile   = aws_iam_instance_profile.ec2_profile.name
  user_data = file("install.sh")
  tags = { Name = "PHP-Server" }
}


 6. Install & Configure Server Automatically

Create a Bash script (`install.sh`) to install dependencies:


!/bin/bash

 Update system packages
sudo yum update -y

 Install Apache & PHP
sudo yum install -y httpd php php-cli php-mysqlnd php-json php-opcache php-mbstring php-xml unzip

 Start Apache & Enable Auto-Start
sudo systemctl start httpd
sudo systemctl enable httpd

 Install MySQL Client
sudo yum install -y mysql

Deploy PHP API and dictionary files
sudo mkdir -p /var/www/html
sudo cp -r /home/ec2-user/app/php-api/ /var/www/html/
sudo cp /home/ec2-user/app/dictionary.txt /var/www/html/
sudo chmod -R 755 /var/www/html
sudo chown -R apache:apache /var/www/html

 Restart Apache
sudo systemctl restart httpd


 7. Deploy Database using RDS


resource "aws_db_instance" "php_db" {
  allocated_storage    = 20
  engine              = "mysql"
  engine_version      = "8.0"
  instance_class      = "db.t3.micro"
  username           = "admin"
  password           = "your-secure-password"
  publicly_accessible = false
  vpc_security_group_ids = [aws_security_group.ec2_sg.id]
}


 8. Store Database Credentials in AWS Secrets Manager


resource "aws_secretsmanager_secret" "db_secret" {
  name = "php_db_secret"
}

resource "aws_secretsmanager_secret_version" "db_secret_version" {
  secret_id     = aws_secretsmanager_secret.db_secret.id
  secret_string = jsonencode({
    username = "admin"
    password = "your-secure-password"
    host     = aws_db_instance.php_db.endpoint
  })
}


 9. Deploy Load Balancer for High Availability


resource "aws_lb" "php_lb" {
  name               = "php-lb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.ec2_sg.id]
  subnets            = ["subnet-xxxxxxxx", "subnet-yyyyyyyy"]
}

 10. Apply Terraform Configuration

 Initialize Terraform
  terraform init


 Plan Deployment
  terraform plan


 Apply Deployment
  terraform apply -auto-approve


 11. Test & Monitor Deployment

 Check Application
- Visit `http://your-ec2-public-ip` to check the PHP API
- SSH into the server and verify the application is running

 Monitor AWS Logs
- CloudWatch Logs → Monitor EC2 logs
- EC2 Dashboard → Check instance status
