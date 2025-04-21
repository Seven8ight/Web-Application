Create Database Transportapp_db;
use Transportapp_db;

CREATE TABLE Passengers (
passenger_id INT AUTO_INCREMENT PRIMARY KEY,
passenger_name varchar(255) not null,
passenger_email varchar (255) unique not null,
passenger_phone_number int
);

CREATE TABLE Driver (
Driver_id int auto_increment primary key,
Driver_name varchar(255) not null,
Driver_license_number int not null,
Driver_phone_number int not null
);
 
CREATE TABLE Dispatcher (
Dispatcher_id int auto_increment primary key,
Dispatcher_name varchar(255) not null,
Dispatcher_phone_number int not null
);

CREATE TABLE System_Administrator(
Admin_id int auto_increment primary key,
Admin_username varchar(255) not null,
Admin_password varchar(255) not null 
);

CREATE TABLE Route (
route_id int auto_increment primary key,
origin varchar(255) not null,
destination varchar(255) not null,
estimated_time int
);

CREATE TABLE Trip(
trip_id int auto_increment primary key,
route_id int references Route(route_id),
driver_id int references Driver(driver_id),
dispatcher_id int references Dispatcher(dispatcher_id),
departure_time int,
arrival_time int,
trip_status varchar(255) not null
);

CREATE TABLE Feedback (
feedback_id int auto_increment primary key,
passenger_id int references Passenger(passenger_id),
trip_id int references Trip(trip_id),
rating int
 );







