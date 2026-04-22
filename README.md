# Application-web-de-gestion-et-de-choix-de-plantes-d-int-rieur
Application web intelligente permettant de recommander des plantes d’intérieur adaptées au profil et à l’environnement de l’utilisateur. Elle intègre la gestion des données botaniques, des vendeurs et des stocks, offrant une interface simple pour le choix et l’entretien des plantes
#  Smart Indoor Plants Recommender

> A web application for intelligent selection and care of indoor plants based on user environment, preferences, and botanical data.

---

##  Overview

Smart Indoor Plants Recommender is a web-based system designed to help users choose and maintain indoor plants according to their personal environment and constraints.

The platform connects **three main actors**:
-  End users (individuals)
-  Botanists
-  Specialized sellers

Each actor has a dedicated interface and access level, ensuring a clear separation of responsibilities and data management.

---

##  Project Objectives

- Recommend suitable indoor plants using environmental and personal user data
- Centralize botanical knowledge (needs, diseases, treatments)
- Enable sellers to manage stock and product catalog efficiently
- Provide a structured and scalable database-driven system
- Offer a simple and intuitive web interface

---

## User Roles

###  End User (Customer)
Users can define their indoor environment and preferences:

- Location
- Gardening experience level
- Watering availability
- Environmental conditions:
  - Light exposure
  - Room size
  - Humidity level
  - Presence of pets
- Plant preferences

 The system generates a **personalized plant recommendation list** using SQL-based filtering on the database.

---

###  Botanist
Responsible for enriching the botanical database:

- Scientific name
- Botanical family
- Toxicity level
- Care requirements:
  - Watering
  - Light
  - Humidity
  - Temperature
- Common plant diseases
- Treatments and prevention methods

---

###  Seller
Manages plant availability and commercial data:

- Product catalog
- Pricing
- Stock management
- Plant varieties and accessories

Sellers can also analyze user profiles to identify high-demand plants.

---

## System Features

The system manages and connects:

- Users and profiles
- Plant database
- Environmental constraints
- Botanical requirements
- Diseases & treatments
- Sellers and inventory
- User interactions (wishlist, preferences)


---

##  Key Concept

The recommendation engine is based on:
- Structured SQL filtering
- Matching between:
  - User environment parameters
  - Plant biological requirements

---

##  Technologies 

- Frontend: HTML, CSS, JavaScript
- Backend: PHP 
- Database: MySQL
- Tools: Git, GitHub

---

##  Database Entities 

- Users
- Plants
- EnvironmentalProfiles
- BotanicalCharacteristics
- Diseases
- Treatments
- Sellers
- Inventory
- Wishlist

---

##  Author

Student in Computer Engineering  
Passionate about Full-Stack Development & Intelligent Systems

---

##  License

This project is developed for academic purposes.

##  Architecture (Conceptual)
