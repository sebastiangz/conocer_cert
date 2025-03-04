# CONOCER Certification Management

This Moodle plugin provides a comprehensive system for managing CONOCER (Mexican National Council for Standardization and Certification of Labor Competencies) certifications within a Moodle installation.

## Overview

The CONOCER Certification Management plugin allows educational institutions and certification centers to administer the complete lifecycle of competency certifications, including:

- Managing candidates seeking certification
- Handling document uploads and verification
- Assigning external evaluators to certification candidates
- Tracking certification processes from application to completion
- Generating and verifying official certificates
- Registering companies as certification endorsers
- Providing detailed reporting and statistics

## Features

### For Candidates
- Apply for certification in specific competencies and levels
- Upload required documentation (ID, address proof, etc.)
- Track certification progress through personalized dashboard
- Download certificates upon successful completion
- Receive automated notifications at key process stages

### For Evaluators
- View assigned candidates waiting for evaluation
- Submit evaluation results with detailed assessments
- Monitor workload and performance statistics
- Manage personal evaluator profile and competencies

### For Companies
- Register as certifying entities
- Select competencies of interest
- Track certification processes within the company
- Generate company-specific reports

### For Administrators
- Manage the entire certification process
- Review and approve candidate documents
- Assign evaluators to certification candidates
- Configure competencies and their levels
- Generate comprehensive reports and statistics
- Customize notification templates

## Installation

### Prerequisites
- Moodle 4.1 or higher
- PHP 7.4 or higher
- Database: MySQL 5.7+ / MariaDB 10.2+ / PostgreSQL 9.6+

### Installation Steps
1. Download the plugin package
2. Extract the folder and place it in your Moodle installation under `/local/`
3. Rename the folder to `conocer_cert` if necessary
4. Visit your Moodle site as an administrator to complete the installation
5. Configure the plugin settings under Site Administration > Plugins > Local Plugins > CONOCER Certification

## Configuration

After installation, you'll need to set up:

1. **Basic settings**:
   - Institution name and logo
   - Certificate expiration policies
   - Document upload restrictions

2. **Competencies**:
   - Add CONOCER standard competencies with their official codes
   - Configure available levels for each competency
   - Define required documentation

3. **User Roles**:
   - Assign system administrators
   - Register external evaluators
   - Set up company administrators

## Usage

The plugin adds a main navigation item "CONOCER Certification" that adapts to the user's role:

- **Candidates** see their certification applications and progress
- **Evaluators** see their assigned candidates and evaluation tools
- **Companies** see their registered competencies and candidates
- **Administrators** see comprehensive management options

## Security Features

The plugin includes robust security features:

- Document validation for potentially malicious content
- Secure certificate verification system
- Role-based access controls
- Detailed security logging

## Scheduled Tasks

The plugin includes the following automated tasks:

- Send reminders to candidates with pending documents
- Notify evaluators of pending evaluations
- Process certificate expiration dates
- Generate periodic reports for administrators

## Customization

The system uses Mustache templates that can be overridden in your theme to customize the appearance of:

- Dashboard interfaces
- Certificate templates
- Notification messages
- Report layouts

## Support and Development

- **Author**: Sebastian Gonzalez Zepeda
- **Email**: sgonzalez@infraestructuragis.com
- **Copyright**: 2025 
- **License**: [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html)

For bug reports, feature requests, or other inquiries, please contact the author directly.

## Contributing

Contributions to improve the plugin are welcome. Please follow these steps:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Acknowledgements

This plugin was developed to support educational institutions in Mexico that offer CONOCER certifications, providing a comprehensive digital solution to manage the entire certification process.