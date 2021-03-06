<?php
include_once(__DIR__ . "/Db.php");
include_once(__DIR__ . "/Mail.php");
include_once(__DIR__ . "/Hobby.php");
class User
{
    private $userID;
    protected $email;
    private $password;
    private $firstName;
    private $lastName;
    private $buddy;
    private $userBuddy;
    private $userCount;
    private $buddieCount;
    
    protected $token;
    protected $active;


    /**
     * Get the value of email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the value of password
     *
     * @return  self
     */
    public function setPassword($password)
    {
        $this->password = $password;


        //return $this;
    }

    /**
     * Get the value of password
     */
    public function getFirstname()
    {
        return $this->firstName;
    }


    /**
     *Set the value of firstname
     *  @return  self
     */
    public function setFirstname($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get the value of lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }


    /**
     *Set the value of lastName
     *  @return  self
     */
    public function setLastname($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    //get en set buddyCheckbox
    public function getBuddy()
    {
        return $this->buddy;
    }

    public function setBuddy($buddy)
    {
        $this->buddy = $buddy;
    }

    public function getUserBuddy()
    {
        return $this->userBuddy;
    }

    public function setUserBuddy($userBuddy)
    {
        $this->userBuddy = $userBuddy;
    }

    public function getUserCount()
    {
        return $this->userCount;
    }

    public function getBuddyCount()
    {
        return $this->buddyCount;
    }




    public function canILogin()
    {
        $conn = Db::getConnection();

        $statement = $conn->prepare('select * from user where email = :email');
        $email = $this->getEmail();
        $password = $this->getPassword();
        $statement->bindParam(':email', $email);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $result['password'])) {
            $_SESSION['user_id'] = $result['userID'];
            $_SESSION['email'] = $email;
            header('Location: index.php');
            return true;
        } else {
            return false;
        }
    }
    // vraag userId op via de persoon zijn email
    public function getUserId()
    {
        $conn = Db::getConnection();
        $statement = $conn->prepare('select userID from user where email = :email');
        $email = $this->getEmail();
        $statement->bindParam(':email', $email);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    public static function userSearch($search, $email)
    {
        $conn = Db::getConnection();

        $statement = $conn->prepare("select * from user where firstname like :search or lastname like :search and email != :email");
        $statement->bindValue(':email', $email);
        $statement->bindValue(':search', '%' . $search . '%');
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }


    public function registerNewUser()
    {
        $conn = Db::getConnection();

        //Hash the password  

        $this->password = password_hash($this->password, PASSWORD_BCRYPT, ["cost" => 12]);
        $this->token = md5(time() . $this->email);
        //Registratie in database
        $statement = $conn->prepare("INSERT INTO user (firstname, lastname, email, password, buddy, avatar, token, active) values (:firstname, :lastname, :email, :password, :buddy, :avatar, :token, :active)");
        $statement->bindValue(":firstname", $this->firstName);
        $statement->bindValue(":lastname", $this->lastName);
        $statement->bindValue(":email", $this->email);
        $statement->bindValue(":password", $this->password);
        $statement->bindValue(":buddy", $this->buddy);
        $statement->bindValue(":token", $this->token);
        $statement->bindValue(":active", "0");
        $statement->bindValue(":avatar", "default.png");

        $result = $statement->execute();
        if($result){
            $user = $this->getUser();
            $_SESSION['user_id'] = $this->getUserId();
            $content = $this->activatieLink($user['userID'], $user['token']);
            $verkey = $this->getKey();
            Mail::sendMail($verkey['Ver_key'], "Account Activatie", $user['email'],$content);
            $_SESSION['succes'] = "Bevestig je registratie via email";
        }

        return $result;
    }
    public function getKey(){
        $conn = Db::getConnection();
        $statement = $conn->prepare("select * from mailKey");
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result;
    }
    public function searchEmail($searchEmail){  
        $conn = Db::getConnection();
        $statement = $conn->prepare("select * from user where email = :email");
        $statement->bindValue(":email", $searchEmail);
        $statement->execute();
        $count = $statement->rowCount();
        //var_dump($count);
        $result = "";
        if($count > 0){
            $result = 'bestaat';
        } else {
            $result =  'bestaat nog niet';
        }
        return $result;
    }

    public function getUser(){
        $conn = Db::getConnection();
        $statement = $conn->prepare('select * from user where email = :email');
        $statement->bindParam(':email', $this->email);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result;
    }
    public function activatieLink($id,$token){
        $link = "<a href='http://www.imdamigos.site/activatie.php?token=$token&userID=$id'>" . 'Activeer Account' . '</a>';
        return $link;
    }
    
    //activeer status update naar 1
    public function activate($token, $id){
        $conn = Db::getConnection();
        $statement = $conn->prepare('update user set active=1 where userID = :userID and token = :token');
        $statement->bindParam(':userID', $id);
        $statement->bindParam(':token', $token);
        $result = $statement->execute();
        if($result){
            $user = $this->getUserById($id);
            $_SESSION['user_id'] = $user;
            //$_SESSION['user_id'] = $id;
            header("Location: hobby.php");
        }

        return $result;
    }
    
    public function getUserById($id)
    {
        $conn = Db::getConnection();
        $statement = $conn->prepare('select * from user where userID = :userID');
        $statement->bindParam(':userID', $id);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    //om al de buddies uit de database te halen
    static function getAllBuddies($userID)
    {
        $conn = Db::getConnection();
        $statement = $conn->prepare("SELECT * FROM buddies INNER JOIN user ON (CASE WHEN buddy1ID = :userID THEN buddy2ID = user.userID WHEN buddy2ID = :userID THEN buddy1ID = user.userID END)");
        //$statement = $conn->prepare("SELECT u.firstname, u.lastname* FROM buddies as b, user u 
        //WHERE (u.userID = b.buddy1ID OR u.userID = b.buddy2ID) AND (buddy1ID = :currentUser OR buddy2ID = :currentUser)");
        $statement->bindValue(":userID", $userID);
        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    //database statement om aan te passen of je buddy bent of een buddy zoekt
    public function updateUserBuddy()
    {
        $conn = Db::getConnection();
        $statement = $conn->prepare("UPDATE user SET buddy= :buddy WHERE userID = :userBuddy");
        $statement->bindParam(':buddy', $this->buddy);
        $statement->bindParam(':userBuddy', $this->userBuddy);
        $result = $statement->execute();
        return $result;
    }

    // functie maken om alle gematchte buddies te displayen

    public function whoAreBuddies(){
        $conn = Db::getConnection();

        $statement = $conn->prepare("
        SELECT 
        u1.firstname as firstnameBuddy1, 
        u1.lastname as lastnameBuddy1,
        u1.avatar as avatar1,
        u2.firstname as firstnameBuddy2, 
        u2.lastname as lastnameBuddy2, 
        u2.avatar as avatar2
        FROM 
        buddies as b, user u1, user u2
        WHERE
        u1.userID = b.buddy1ID AND
        u2.userID = b.buddy2ID AND
        b.status = true
        ");
        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function AllUsers(){
        $conn = Db::getConnection();
        $statement = $conn->prepare('SELECT COUNT(*) AS numbersOfUsers FROM user');
        $statement->execute();
        $boo = $statement->fetch();
        return $boo;
       
    }

    public function AllMatchedBuddies(){
        $conn = Db::getConnection();
        $statement = $conn->prepare('SELECT COUNT(*) AS numbersOfMatchedBuddies FROM buddies');
        $statement->execute();
        $booBoo = $statement->fetch();
        return $booBoo;
    }

    public function getUserData($userID){
        $conn = Db::getConnection();
        $statement = $conn->prepare('select * from user where userID = :userID');
        $statement->bindParam(':userID', $userID);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    public function lokalen(){
        $conn = Db::getConnection();

        $statement = $conn->prepare("select * from location");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function lokaalInfo($lokaal){
        $conn = Db::getConnection();

        $statement = $conn->prepare("select description from location where location = :lokaal");
        $statement->bindParam(':lokaal', $lokaal);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result;
    }



    /**
     * Get the value of token
     */ 
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the value of token
     *
     * @return  self
     */ 
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get the value of active
     */ 
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set the value of active
     *
     * @return  self
     */ 
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }
}
