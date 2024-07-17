Todo 
[1]: Middleware to control customer authentication [Done]
[2]: Audit Trails and all other security related operations
[3]: Controller for Confirmations of Order, Bagging, Checker, Payment and Delivery
[4]: Inventory management and other management related operations

Payment Url is 127.0.0.1:8000/api/payment/93450514/22482565
Format is 'payment/{UserId}/{OrderId}'

I will just have to provide the SuccessUrl and the Callback url to navigate to 

Success url will be the backend api 
example is [https://hydcapi.hydottech.com/api/ExecuteWhenPaymentIsDone]

Callback url will just be a normal frontend url 
example is [https://hydotcommerce.hydottech.com]



