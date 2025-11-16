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
Before using the program in .env change variable "IPSTACK_API_KEY" to new API_KEY. Current API_KEY has limit of request per month.
Other variables in .env file

IP_LIFE_CYCLE_SECONDS=86400 // curently 1 day as requirments of the task
IP_MAX_FIND_QUNANTITY=5 // maximum allowed IP addreses search at once per API request
IP_MAX_DELETE_QUANTITY=10 // maximum alowed IP addresses deletion at once per API request 
IP_MAX_BAN_QUANTITY=10 // maximum allowed IP bans per API request
IP_MAX_UNBAN_QUANTITY=10 // maximum allowed IP unbans per API request

 - API ENDPOINTS 

 1. /api/ip/find/{address} - valid IPV4 addreses supports multiple IP's but needs to be separated by comma ",". Used to get data of the IP(s). Informs user if the ip format is invalid or the IP is blacklisted.
 2. /api/ip/blacklist/{ip} [patch] - valid IPV4 addreses supports multiple IP's but needs to be separated by comma "," Used to blacklist IP(s). Informs user if the ip is : not found in the list [1],is already int the blacklist.
 3. /api/ip/blacklist/{ip} [delete]- valid IPV4 address, supports multiple IP's but need to be separated by comma "," Used to remove IP(S) from the blacklist. Informs user if the ip is not found or ip is not in blacklist.
 4. /api/ip/{ips} - alid IPV4 address, supports multiple IP's but need to be separated by comma ",". Used to delete IP(s). Informs the user if IP is not found. Blacklist entry is automatically deleted "CASCADE"[2]

# Design desitions : 
 - as much as possible separate logic where it is possible. Used service class to lithen entity and controllers.
 - Created custom Exception class but for faster exectution of the tasks deciced against it. Errors are handeled by custom messages. 
 - Used try catch block and condition cheking on enviroment for easier code debuging.
 - Used CASCADE on blacklist to implemt idea "datbase should be able to function regardles backend implementation"
 - Left in the code base only the minimum code. Not much use to think ahead when there are lots unknows about programs usage. Too much overthinking (or over preparness ) might waste time.

## Future release notes :
- If external API allows bulk Ednpoint then Implement bulk endpoint call to that API
- Implement API KEY for app usage( users, roles , permissions will have to be done )
- !Implement error handling from EXTERNAL API (due to limited time it was not done.)

## Questions 
- Should BAN of IP persist after the IP is deleted ? If yes what are the criteria of. (Current Implementation is that bans dissapear on deletion of IP)
- [1] If Ip is not found during the ban should the program add the IP and then ban it ?
- [2] On Ip delete should that IP's ban remain ?
