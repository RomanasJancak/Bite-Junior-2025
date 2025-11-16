# Junior PHP Developer (Symfony focus) Assignment 

## Task Description
The goal of this assignment is to create a RESTful API using Symfony 6 and PHP 8.4 that manages IP address information. The API will interact with a third-party service, ipstack.com, to fetch IP data and will implement a caching and blacklisting system.

The project should be built with given scaffolding, demonstrating your ability working with symfony, configure services, handle API calls, and interact with a database.

This task is scoped to be doable in ~4 hours (one evening) for a junior developer with some Symfony knowledge.

## Core Functionality
1. Retrieve IP Information
    - When a request is made for a specific IP address, the application must first check its local database.
    - If the IP exists in the database and the data is not older than one day, return the cached information.
    - If the IP exists but the data is older than one day, fetch fresh data from the ipstack.com API, update the record in the database, and then return the updated information.
    - If the IP does not exist in the database, fetch the data from the ipstack.com API, save it to the database, and return the response.
2. Delete IP Information
    - This endpoint must allow an IP address to be removed from the local database.
    - Return a success message upon successful deletion.
    - Return an appropriate error if the IP is not found.
3. Blacklist Management
    - Create two new endpoints to manage a blacklist of IP addresses.
    - When an IP is in the blacklist, any attempt to retrieve its information using the endpoint must be blocked. The API should return an error response without making any external API calls.
    - A blacklisted IP should be a separate entity in the database, with a clear relationship to the IP data.
4. Extra (optional, bonus points)
   - Bulk endpoints
   
## Getting Started
A Docker environment has been provided for your convenience.

1. Make sure you have Docker and Docker Compose installed on your system
2. Clone this repository
3. Navigate to the repository directory
4. Run `docker compose up -d`
5. Run `docker compose exec php composer install`
6. Access the API at http://localhost:8080/api/doc

## Submission:
- Create a git (Github/Gitlab) repository with your solution
- Include a README.md explaining how to run your code and any design decisions
- Ensure your code is well-commented and follows best practices

## Evaluation Criteria:
- All endpoints must be documented using OpenAPI annotations.
- Correctness of the implementation
- Understanding of RESTful API building principles
- Proper use of built-in PHP & Symfony features
- Efficient implementation of the functionality
- Code organization and readability
- Error handling
- Test coverage


## Runing the code :

## Future release notes :
- If external API allows bulk Ednpoint then Implement bulk endpoint call to that API