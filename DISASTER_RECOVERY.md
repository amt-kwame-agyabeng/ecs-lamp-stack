# Disaster Recovery Setup - ECS LAMP Stack

## Overview

This document outlines the disaster recovery (DR) implementation for the ECS LAMP Stack, providing cross-region redundancy and failover capabilities.

## Architecture

![DR Architecture](DisasterRecoverArchitecture.svg)


## Primary Region (eu-central-1 - Frankfurt)*
- ECS Cluster with LAMP application service (running tasks)
- Application Load Balancer (ALB)
- RDS MySQL database (primary, possibly Multi-AZ)

## Disaster Recovery Region (eu-west-1 - Ireland)*
- ECS Cluster with LAMP application service (scaled to 0 tasks for pilot light)
- Application Load Balancer (ALB)
- RDS MySQL read replica (continuously replicating from primary)

## Deployment Instructions

These instructions assume you have the AWS CLI and AWS Copilot CLI installed and configured with appropriate AWS credentials.

1.  **Clone this repository:**
    ```bash
    git clone [https://github.com/amt-kwame-agyabeng/ecs-lamp-stack.git](https://github.com/amt-kwame-agyabeng/ecs-lamp-stack.git)
    cd ecs-lamp-stack
    ```

2.  **Initialize Copilot Application:**
    This command sets up the basic Copilot application structure.
    ```bash
    copilot app init <your-app-name>
    ```

3.  **Deploy Primary Environment (eu-central-1):**
    Create and deploy the primary environment. Ensure `eu-central-1` is set as the region for this environment.
    ```bash
    copilot env init --name prod --profile default --region eu-central-1
    copilot env deploy --name prod
    ```

4.  **Deploy DR Environment (eu-west-1):**
    Create and deploy the disaster recovery environment. Ensure `eu-west-1` is set as the region for this environment.
    ```bash
    copilot env init --name dr-prod --profile default --region eu-west-1
    copilot env deploy --name dr-prod
    ```

5.  **Deploy LAMP Service to Primary:**
    Deploy your `ecslamp-service` to the primary environment. This will create the ALB, ECS service, and tasks.
    ```bash
    copilot svc deploy --name ecslamp-service --env prod
    ```

6.  **Deploy LAMP Service to DR (Pilot Light):**
    Deploy your `ecslamp-service` to the DR environment, ensuring `count` is set to `0` in `copilot/ecslamp-service/manifest.yml` for this environment before deploying.
    ```bash
    copilot svc deploy --name ecslamp-service --env dr-prod
    ```

7.  **Create Primary RDS Database (in eu-central-1):**
    * Manually create the RDS MySQL DB instance `lamp-app-db` in `eu-central-1` via the RDS Console.
    * Configure security groups to allow traffic from `copilot-ecslamp-prod-ALB-SecurityGroup`.
    * Update your `copilot/ecslamp-service/manifest.yml` to include the `MYSQL_HOST` environment variable pointing to your primary RDS endpoint.
    * Redeploy `copilot svc deploy --name ecslamp-service --env prod`.

8.  **Create Cross-Region RDS Read Replica (in eu-west-1):**
    * From the `eu-central-1` RDS Console, select `lamp-app-db`.
    * Actions > Create read replica.
    * Set **Destination AWS Region** to `eu-west-1`.
    * Set **DB instance class** to `db.t3.micro` (to avoid capacity issues).
    * Ensure **VPC** is `copilot-ecslamp-dr-prod-VPC` and select/create a DB Subnet Group including private subnets in `eu-west-1a` and `eu-west-1b`.
    * Configure security groups to allow traffic from `copilot-ecslamp-dr-prod-ALB-SecurityGroup`.

9.  **Create Route 53 Health Checks:**
    * In Route 53 Console, create two health checks:
        * `ecslamp-primary-alb-healthcheck` monitoring primary ALB DNS (`ecslam-Publi-OD2fyHpOMv6x-422903046.eu-central-1.elb.amazonaws.com`)
        * `ecslamp-dr-alb-healthcheck` monitoring DR ALB DNS (`ecslam-Publi-mOEAkm0qPNAe-1859426312.eu-west-1.elb.amazonaws.com`)
        * Ensure "Domain name" is selected, not "IP address."


## Disaster Recovery (DR) Trigger Steps (Failover Drill)

Follow these steps to simulate a disaster and activate the DR environment.

1.  **Simulate Disaster Trigger Detected:**
    * **For practice:** Manually scale down your primary ECS service in `eu-central-1` to `0` tasks via the ECS Console or `copilot svc deploy --name ecslamp-service --env prod --count 0`.
    * Observe the `ecslamp-primary-alb-healthcheck` in Route 53 turn `Unhealthy`.

2.  **Activate Pilot Light:**
    * **RDS Promotion:** In the RDS Console (`eu-west-1`), select `lamp-app-db-dr` read replica and promote it to a standalone instance. Wait for `available` status.
    * **ECS Scale Up:** Update `copilot/ecslamp-service/manifest.yml` to set `count: 1` (or your desired number) and deploy to DR: `copilot deploy --name ecslamp-service --env dr-prod`. Wait for tasks to be `Running`.

3.  **Simulate DNS Failover (Manual Access for Practice):**
    * Access your application using the DR ALB's DNS name: `ecslam-Publi-mOEAkm0qPNAe-1859426312.eu-west-1.elb.amazonaws.com`.

4.  **Data Validation & Smoke Test:**
    * Verify existing data.
    * Add new data and confirm persistence.
    * Check application performance.


## Monitoring & Alerts

### Health Checks
```bash
# Check primary environment
curl -I http://ecslam-Publi-OD2fyHpOMv6x-422903046.eu-central-1.elb.amazonaws.com

# Check DR environment
curl -I http://<dr-alb-endpoint>
```


## Testing

### Test Checklist
- [ ] DR environment deploys successfully
- [ ] Application loads and functions correctly
- [ ] Database connectivity works
- [ ] User registration system operational
- [ ] Performance meets requirements


## Security Considerations

- Same security groups and IAM roles in both regions
- Encrypted database connections
- Secure environment variable handling
- Regular security updates

## Cost Optimization

### Current Costs
- DR environment runs continuously (1 ECS task)
- Separate RDS instance in DR region

### Optimization Options
- Scale DR to 0 tasks when not needed
- Use RDS snapshots instead of running instance
- Implement automated scaling based on health checks

## Troubleshooting

### Common Issues
1. **Database Connection Failures**
   - Verify RDS security groups
   - Check VPC connectivity
   - Validate environment variables

2. **ECS Service Won't Start**
   - Check resource allocation
   - Verify Docker image availability
   - Review CloudWatch logs

3. **Load Balancer Health Checks Failing**
   - Confirm application responds on port 80
   - Check security group rules
   - Verify health check path

### Debug Commands
```bash
# View DR service logs
copilot svc logs --name ecslamp-service --env dr-prod --follow

# Execute commands in DR container
copilot task exec --env dr-prod --command "/bin/bash"

# Check DR database connectivity
copilot task exec --env dr-prod --command "mysql -h $DB_HOST -u $DB_USER -p"
```



