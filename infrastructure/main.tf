provider "aws" {
  region = var.region
}

resource "aws_db_instance" "cracker_db" {
  identifier           = "password-cracker-db"
  engine              = "mysql"
  engine_version      = "8.0"
  instance_class      = "db.t3.micro"
  allocated_storage   = 20
  username            = var.db_username
  password            = var.db_password
  db_name             = "password_cracker"
  vpc_security_group_ids = [aws_security_group.db_sg.id]
  skip_final_snapshot = true
}

resource "aws_security_group" "db_sg" {
  name        = "cracker-db-sg"
  description = "Allow MySQL access"

  ingress {
    from_port   = 3306
    to_port     = 3306
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"] # Tighten this in production
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_secretsmanager_secret" "db_credentials" {
  name = "password-cracker/credentials"
}

resource "aws_secretsmanager_secret_version" "db_credentials_version" {
  secret_id = aws_secretsmanager_secret.db_credentials.id
  secret_string = jsonencode({
    db_host = aws_db_instance.cracker_db.address,
    db_name = "password_cracker",
    db_user = var.db_username,
    db_pass = var.db_password
  })
}

variable "region" {
  default = "us-east-1"
}

variable "db_username" {
  sensitive = true
}

variable "db_password" {
  sensitive = true
}