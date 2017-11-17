FORMAT: 1A
HOST: http://api.b33f.io/

# b33f

BPI: beef programming interface


## Register [/api/register]

### Register [POST]

+ Request (application/json)

        {
            "first_name": "Fontenot",
            "last_name": "Mark",
            "username": "user1",
            "user_type": "admin",
            "password": "pass",
            "password_check": "pass",
            "email": "mfontenot@smu.edu",
            "phone_number": "1234567890"
        }   
    
+ Response 200 (application/json)

        {
            "session_id": "fd80sa9m8fd0sa"
        }
        


## Login [/api/login]

### Login [POST]

+ Request (application/json)

        {
            "username": "user1",
            "password": "pass"
        }   
    
+ Response 200 (application/json)

        {
            "session_id": "fd80sa9m8fd0sa"
        }
        
+ Response 401 (application/json)

        {
            "error": "Not authorized"
        }
        

 
## Logout [/api/logout]

### Logout [POST]

+ Request (application/json)

        {
            "session_id": "fd80sa9m8fd0sa"
        }   
    
+ Response 200 (application/json)

        {
            "message": "Logged out"
        }
        
+ Response 401 (application/json)

        {
            "error": "Not authorized"
        }  
        
        
## Account details [/api/account]

### Account details [A]

+ Request (application/json)

        {
            "session_id": "fd80sa9m8fd0sa"
        }   
    
+ Response 200 (application/json)

        {
            "username": "user1",
            "name": "Mark Fontenot",
            "email": "mfontenot@smu.edu",
        }   
        
+ Response 401 (application/json)

        {
            "error": "Not authorized"
        }

## Channels [/api/channels/]
### List All Channels [GET]
+ Response 200 (application/json)

        {
            "channels" = [
                {   
                    "channel_name": "Programming", 
                    "channel_id": 1, 
                    "total_debates": 6
                },
                {   
                    "channel_name": "Sports", 
                    "channel_id": 3, 
                    "total_debates": 44
                }
            ]
        }
        
## Debates Per Channel [/api/channels/{channel_id}]
### List All Debates in Channel [GET]
+ Response 200 (application/json)

        {
            "debates" = [
                {
                    "debate_id": 1,
                    "debate_title": "Vim is better than emacs",
                    "opponent_id": 1,
                    "opponent_username": "asdf",
                    "proponent_id": 2,
                    "proponent_username": "qwerty",
                    "channel_id": 1,
                    "channel_name": "Programming",
                    "create_date": 4382108430,
                    "update_date": 4480932082,
                    "views": 2000,
                    "up_votes": 500,
                    "down_votes": 123
                }
            ]
        }
        

## Debates [/api/debates]

### List Debates [GET]

+ Response 200 (application/json)

        [
            {
             "debate_id": 1,
             "debate_title": "Vim is better than emacs",
             "debate_url": "/debates/1",
             "opponent_id": 1,
             "proponent_id": 2,
             "channel_id": 1,
             "channel_name": "Programming",
             "create_date": 4382108430,
             "update_date": 4480932082,
             "views": 2000,
             "up_votes": 500,
             "down_votes": 123
             },
        ]
        
        
### Create Debate [POST]


+ Request (application/json)

        {
            "debate_title": "Plants should have the right to vote",
            "channel_id": 2
        }
    
+ Response 200 (application/json)

        {
            "debate_id": 2,
            "debate_title": "Plants should have the right to vote",
            "debate_url": "/debate/2",
            "proponent_id": 1,
            "channel_id": 2,
            "create_date": 4382108430,
            "update_date": 4382108430,
            "views": 0,
            "up_votes": 0,
            "down_votes": 0
        }
        
        
        

## Get debate by ID [/api/debates/{debate_id}]
+ Parameters
    + debate_id (number) - ID of the debate  
### Get debate by ID [GET]
POINTS MUST BE LISTED IN ORDER!!
+ Response 200 (application/json)

        [
            "debate": {
                 "debate_id": 1,
                 "debate_title": "Vim is better than emacs",
                 "debate_url": "/debates/1",
                 "opponent_id": 1,
                 "proponent_id": 2,
                 "opponent_username": "mkqueenan",
                 "proponent_username": "mfonten",
                 "channel_id": 1,
                 "channel_name": "Programming",
                 "create_date": 4382108430,
                 "update_date": 4480932082,
                 "views": 2000,
                 "up_votes": 500,
                 "down_votes": 123
            },
             "points": [
                {
                    "point_id": 10,
                    "user_id": 1,
                    "username":"mkqueenan",
                    "up_votes": 20,
                    "down_votes": 30,
                    "point_text": "Aloha snackbar"
                },
                {
                    "point_id": 15,
                    "user_id": 2,
                    "username":"mfonten",
                    "up_votes": 20,
                    "down_votes": 3,
                    "point_text": "Minus 10 points"
                }
             ]
             },
        ]
        
        
## Get points by ID [/api/points/{point_id}]

+ Parameters
    + point_id (number) - ID of the comment
    

### Get comment by ID [GET]

+ Response 200 (application/json)

        {
            "point_id": 10,
            "debate_id":  1,
            "user_id":, 1
            "up_votes": 20,
            "down_votes": 30,
        }

        
        
## Create Comment [/api/comments]
    

### Create comment [POST]
+ Request (application/json)

        {
            "comment_text": "Plants are living beings",
            "post_id": 2,
            "user_id": 1
        }

+ Response 200 (application/json)

        {
            "comment_id": 10,
            "parent_id":  1,
            "up_votes": 0,
            "down_votes": 0,
            "post_id": 1,
            "user_id": 1
        }
