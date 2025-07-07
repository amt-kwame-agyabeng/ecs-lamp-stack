# ECS LAMP Stack

A containerized LAMP (Linux, Apache, MySQL, PHP) stack deployed on AWS ECS with AWS Copilot, featuring a user registration system with database connectivity.

##  Live Demo

**Application URL**: [http://ecslam-Publi-OD2fyHpOMv6x-422903046.eu-central-1.elb.amazonaws.com](http://ecslam-Publi-OD2fyHpOMv6x-422903046.eu-central-1.elb.amazonaws.com)

Try the live application to see the user registration system in action!

## Architecture

![Architecture Diagram](architectural%20diagram.svg)

### System Overview
This project implements a scalable, cloud-native LAMP stack with the following components:

#### Application Layer
- **Frontend**: PHP 8.2 web application with user registration functionality
- **Web Server**: Apache HTTP Server (containerized with mod_rewrite enabled)
- **Framework**: Custom PHP application with Tailwind CSS for styling

#### Infrastructure Layer
- **Container Orchestration**: Amazon ECS with Fargate (serverless containers)
- **Load Balancing**: Application Load Balancer (ALB) with health checks
- **Database**: Amazon RDS MySQL with Multi-AZ deployment
- **Networking**: VPC with public/private subnets across 2 AZs
- **Security**: Security groups, IAM roles, and encrypted connections

#### Deployment Architecture
- **Region**: eu-central-1 (Frankfurt)
- **Availability Zones**: eu-central-1a and eu-central-1b
- **Scaling**: Auto-scaling based on CPU/memory utilization
- **Monitoring**: CloudWatch logs and metrics integration

## Features

### Core Functionality
- **User Registration System**: Complete user registration with form validation
- **Database Integration**: MySQL database with automatic table creation
- **User Management**: Display recent users with timestamps
- **Real-time Feedback**: Success/error messages with auto-hide functionality

### Technical Features
- **Responsive UI**: Modern, clean interface using Tailwind CSS
- **Security**: Password hashing, SQL injection prevention, input validation
- **Scalability**: Auto-scaling ECS service with multiple tasks
- **High Availability**: Multi-AZ deployment with load balancing
- **Container Health Checks**: Automated health monitoring
- **Zero-Downtime Deployments**: Rolling updates with ECS

##  Technology Stack

- **Backend**: PHP 8.2
- **Web Server**: Apache HTTP Server
- **Database**: MySQL (Amazon RDS)
- **Container**: Docker
- **Orchestration**: AWS ECS with Fargate
- **Infrastructure as Code**: AWS Copilot
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Cloud Provider**: AWS (eu-central-1)

## Project Structure

```
ecs-lamp-stack/
├── app/
│   └── index.php              # Main PHP application with user registration
├── copilot/                   # AWS Copilot configuration
│   ├── ecslamp-service/
│   │   └── manifest.yml       # ECS service configuration (CPU, memory, scaling)
│   ├── environments/
│   │   └── production/
│   │       └── manifest.yml   # Production environment config
│   └── .workspace             # Copilot workspace identifier
├── screenshots/               # Deployment and application screenshots
├── Dockerfile                 # Multi-stage container build configuration
├── architecture diagram.svg  # Visual architecture diagram
├── .gitignore                # Git ignore rules
└── README.md                 # This comprehensive documentation
```

### Key Files Description

#### Application Files
- **`app/index.php`**: Complete PHP application with user registration, database connectivity, form validation, and responsive UI
- **`Dockerfile`**: Container configuration with PHP 8.2, Apache, MySQL extensions, and security settings

#### Infrastructure Files
- **`copilot/ecslamp-service/manifest.yml`**: ECS service definition with resource allocation, environment variables, and scaling policies
- **`copilot/environments/production/manifest.yml`**: Production environment configuration with VPC, subnets, and monitoring settings

## Quick Start

### Prerequisites

#### Required Tools
- **AWS CLI**: Version 2.0+ configured with appropriate IAM permissions
- **AWS Copilot CLI**: Version 1.21.0+ for container orchestration
- **Docker**: Version 20.0+ for local development and testing
- **Git**: For version control and repository management

#### AWS Permissions Required
- ECS (Elastic Container Service) full access
- EC2 (Virtual Private Cloud) management
- RDS (Relational Database Service) administration
- IAM (Identity and Access Management) role creation
- CloudFormation stack management
- Application Load Balancer configuration

### Step-by-Step Setup

#### 1. Repository Setup
```bash
# Clone the repository
git clone <repository-url>
cd ecs-lamp-stack

# Verify project structure
ls -la
```

#### 2. Database Configuration

**Option A: Create RDS Instance via AWS Console**
1. Navigate to RDS in AWS Console
2. Create MySQL 8.0 instance (db.t3.micro for testing)
3. Configure security groups to allow ECS access
4. Note the endpoint, database name, username, and password

**Option B: Create RDS Instance via CLI**
```bash
aws rds create-db-instance \
  --db-instance-identifier lamp-app-db \
  --db-instance-class db.t3.micro \
  --engine mysql \
  --master-username admin \
  --master-user-password YourSecurePassword123! \
  --allocated-storage 20 \
  --db-name ecs_db
```

#### 3. Environment Configuration
Update `copilot/ecslamp-service/manifest.yml` with your database details:
```yaml
variables:
  DB_HOST: lamp-app-db.chwqsq8wcvj4.eu-central-1.rds.amazonaws.com
  DB_NAME: ecs_db
  DB_USER: admin
  DB_PASSWORD: YourSecurePassword123!
```

#### 4. Deployment Process
```bash
# Initialize Copilot application
copilot app init ecs-lamp-stack

# Deploy production environment (creates VPC, subnets, ALB)
copilot env deploy --name production

# Deploy the ECS service
copilot svc deploy --name ecslamp-service --env production

# Get application URL
copilot svc show --name ecslamp-service --env production
```

### Local Development

#### Docker Development Setup

1. **Build the Docker image**
   ```bash
   # Build with tag for easy reference
   docker build -t ecs-lamp-stack:latest .
   
   # Verify image creation
   docker images | grep ecs-lamp-stack
   ```

2. **Run with local database (for testing)**
   ```bash
   # Start MySQL container for local testing
   docker run --name mysql-local -d \
     -e MYSQL_ROOT_PASSWORD=rootpass \
     -e MYSQL_DATABASE=ecs_db \
     -e MYSQL_USER=admin \
     -e MYSQL_PASSWORD=password \
     -p 3306:3306 mysql:8.0
   
   # Run application container
   docker run --name lamp-app -d \
     -p 8080:80 \
     -e DB_HOST=host.docker.internal \
     -e DB_NAME=ecs_db \
     -e DB_USER=admin \
     -e DB_PASSWORD=password \
     --link mysql-local:mysql \
     ecs-lamp-stack:latest
   ```

3. **Run with production database**
   ```bash
   docker run -p 8080:80 \
     -e DB_HOST=lamp-app-db.chwqsq8wcvj4.eu-central-1.rds.amazonaws.com \
     -e DB_NAME=ecs_db \
     -e DB_USER=admin \
     -e DB_PASSWORD=YourSecurePassword123! \
     ecs-lamp-stack:latest
   ```

4. **Access and test the application**
   ```bash
   # Open in browser
   open http://localhost:8080
   
   # Or test with curl
   curl -I http://localhost:8080
   
   # View container logs
   docker logs -f lamp-app
   ```

#### Development Workflow

1. **Make code changes** in `app/index.php`
2. **Rebuild container**: `docker build -t ecs-lamp-stack:latest .`
3. **Test locally**: `docker run -p 8080:80 ...`
4. **Deploy to AWS**: `copilot svc deploy --name ecslamp-service --env production`

## 🔧 Configuration

### Service Configuration

The ECS service is configured in `copilot/ecslamp-service/manifest.yml`:

- **CPU**: 256 units
- **Memory**: 512 MiB
- **Port**: 80
- **Scaling**: 1 task (development), 2 tasks (production)
- **Health Check**: HTTP on root path

### Database Schema

The application automatically creates the following table:

```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `DB_HOST` | RDS endpoint | Yes |
| `DB_NAME` | Database name | Yes |
| `DB_USER` | Database username | Yes |
| `DB_PASSWORD` | Database password | Yes |

## Security Features

### Application Security
- **Password Security**: Passwords are hashed using PHP's `password_hash()` function with bcrypt algorithm
- **SQL Injection Prevention**: All database queries use prepared statements with parameter binding
- **Input Validation**: Comprehensive server-side validation for all form inputs
- **Error Handling**: Secure error messages that don't expose system information

### Infrastructure Security
- **Network Isolation**: ECS tasks run in private subnets with NAT Gateway for outbound access
- **Security Groups**: Restrictive firewall rules allowing only necessary traffic
- **IAM Roles**: Least-privilege access for ECS tasks and services
- **Database Security**: RDS instance in private subnet with encrypted connections
- **Container Security**: Non-root user execution and minimal base image

### Security Best Practices Implemented
- Environment variables for sensitive configuration
- Database connection encryption
- Regular security updates via container rebuilds
- Monitoring and logging for security events

## Monitoring & Logging

- **ECS Service Logs**: Available in CloudWatch Logs
- **Application Load Balancer**: Access logs and metrics
- **RDS Monitoring**: Database performance insights
- **Container Insights**: Optional ECS container monitoring

## Deployment Process

1. **Code Changes**: Make changes to the application code
2. **Build**: Docker image is built automatically by Copilot
3. **Deploy**: Use `copilot svc deploy` to update the service
4. **Rolling Update**: ECS performs zero-downtime deployment

## Testing

### Manual Testing

1. **Registration Flow**:
   - Navigate to the application URL
   - Fill out the registration form
   - Verify user creation in the database
   - Check for proper error handling

2. **Database Connectivity**:
   - Verify database connection status
   - Test form submissions
   - Validate data persistence

### Health Checks

- **Application**: HTTP GET on `/` returns 200 OK
- **Database**: Connection test on application startup
- **Load Balancer**: Target group health checks

## Troubleshooting

### Common Issues and Solutions

#### 1. Database Connection Issues
**Symptoms**: "Database Connection Error" message on application

**Diagnosis**:
```bash
# Check RDS instance status
aws rds describe-db-instances --db-instance-identifier lamp-app-db

# Test connectivity from ECS task
copilot task exec --command "mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD -e 'SELECT 1'"
```

**Solutions**:
- Verify RDS security groups allow inbound traffic from ECS security group on port 3306
- Ensure RDS instance is in the same VPC as ECS service
- Check environment variables in service manifest
- Verify database credentials and endpoint

#### 2. Service Deployment Failures
**Symptoms**: Service fails to start or becomes unhealthy

**Diagnosis**:
```bash
# Check service status and events
copilot svc status --name ecslamp-service --env production

# View detailed CloudWatch logs
copilot svc logs --name ecslamp-service --env production --follow

# Check ECS service events
aws ecs describe-services --cluster copilot-production --services ecslamp-service
```

**Solutions**:
- Verify Docker image builds successfully locally
- Check resource allocation (CPU: 256, Memory: 512 minimum)
- Ensure all environment variables are set correctly
- Verify application responds on port 80

#### 3. Load Balancer Health Check Failures
**Symptoms**: Targets marked as unhealthy in ALB

**Diagnosis**:
```bash
# Check target group health
aws elbv2 describe-target-health --target-group-arn <target-group-arn>

# Test health check endpoint
curl -I http://<alb-dns-name>/
```

**Solutions**:
- Verify application responds with HTTP 200 on root path `/`
- Check security group allows traffic from ALB to ECS tasks on port 80
- Ensure health check timeout and interval settings are appropriate

### Advanced Debugging

#### Container-Level Debugging
```bash
# Execute shell in running container
copilot task exec --command "/bin/bash"

# Check Apache error logs
copilot task exec --command "tail -f /var/log/apache2/error.log"

# Test PHP configuration
copilot task exec --command "php -m | grep mysqli"

# Check environment variables
copilot task exec --command "env | grep DB_"
```

#### Network Debugging
```bash
# Test database connectivity from container
copilot task exec --command "telnet $DB_HOST 3306"

# Check DNS resolution
copilot task exec --command "nslookup $DB_HOST"

# Verify security group rules
aws ec2 describe-security-groups --group-ids <security-group-id>
```

#### Performance Debugging
```bash
# Monitor resource utilization
aws cloudwatch get-metric-statistics \
  --namespace AWS/ECS \
  --metric-name CPUUtilization \
  --dimensions Name=ServiceName,Value=ecslamp-service \
  --start-time 2023-01-01T00:00:00Z \
  --end-time 2023-01-01T23:59:59Z \
  --period 300 \
  --statistics Average
```

## Scaling and Performance

### Horizontal Scaling (Recommended)

#### Manual Scaling
```yaml
# In copilot/ecslamp-service/manifest.yml
environments:
  production:
    count: 5  # Scale to 5 tasks
```

#### Auto Scaling Configuration
```yaml
# Advanced auto-scaling setup
count:
  min: 2
  max: 10
  cooldown:
    scale_in_cooldown: 300s   # Wait 5 minutes before scaling in
    scale_out_cooldown: 120s  # Wait 2 minutes before scaling out
  target_cpu: 70              # Scale out when CPU > 70%
  target_memory: 80           # Scale out when Memory > 80%
```

### Vertical Scaling

#### Resource Allocation Guidelines
```yaml
# For light workloads (< 100 concurrent users)
cpu: 256
memory: 512

# For medium workloads (100-500 concurrent users)
cpu: 512
memory: 1024

# For heavy workloads (500+ concurrent users)
cpu: 1024
memory: 2048
```


##  Useful Links

- [AWS Copilot Documentation](https://aws.github.io/copilot-cli/)
- [Amazon ECS Documentation](https://docs.aws.amazon.com/ecs/)

## Project Highlights

- **Production-Ready**: Deployed on AWS with high availability and auto-scaling
- **Security-First**: Implements industry best practices for web application security
- **Modern Stack**: Uses latest PHP 8.2, containerized with Docker, orchestrated with ECS
- **Responsive Design**: Clean, modern UI that works on all devices
- **Infrastructure as Code**: Complete deployment automation with AWS Copilot
- **Monitoring Ready**: Integrated with CloudWatch for comprehensive observability

---

