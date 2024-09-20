<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AuditTrialController;
use App\Models\Menu;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImages;
use Carbon\Carbon;

class MenuCategoryProduct extends Controller
{

    protected $audit;


    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }

function CreateMenu(Request $req){
    $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Create_Menu");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
        $s = new Menu();
        $s->MenuId = $this->IdGenerator();

        if($req->filled("MenuName")){
            $s->MenuName = $req->MenuName;
        }

        $saver = $s->save();

        if($saver){

            $message = "Menu created successfully";
            $message2 = "Created ".$s->MenuName." menu";
            $this->audit->Auditor($req->AdminId, $message2);

            return response()->json(["message"=>$message],200);
        }
        else{
            return response()->json(["message"=>"Failed to create a menu"],400);
    }
}

function ViewMenu(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Menu::get();
    return $s;
}

function ViewMenuProduct(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Product::where("Quantity",">",0)->where("MenuId",$req->MenuId)->get();
    return $s;
}



function DeleteMenu(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Delete_Menu");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}
    $s = Menu::where("MenuId",$req->MenuId)->first();
    if($s==null){
        return response()->json(["message"=>"Menu not found"],400);
    }

    $saver = $s->delete();
    if($saver){
        $this->audit->Auditor($req->AdminId, "Deleted ".$s->MenuName." menu");
        return response()->json(["message"=>"Menu deleted"],200);
    }
    else{
        return response()->json(["message"=>"Failed to delete Menu Item"],400);
    }




}

function CreateCategory(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Create_Category");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
    }

   $s = new Category();

    $s->CategoryId = $this->IdGenerator();
    if($req->hasFile("CategoryPicture")){
        $s->CategoryPicture = $req->file("CategoryPicture")->store("","public");
    }

    if($req->filled("CategoryName")){
        $s->CategoryName = $req->CategoryName;
    }

    if($req->filled("Section")){
        $s->Section = $req->Section;
    }

    $saver = $s->save();

    if($saver){

        $message = "Category created successfully";
        $message2 = "Created ".$s->CategoryName." category";
        $this->audit->Auditor($req->AdminId, $message2);

        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to create a category"],400);
    }

}

function UpdateCategory(Request $req){

    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Update_Category");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }

   $s = Category::where("CategoryId",$req->CategoryId)->first();
    if($s==null){
        return response()->json(["message"=>"Category does not exist"],400);
    }

    if($req->hasFile("CategoryPicture")){
        $s->CategoryPicture = $req->file("CategoryPicture")->store("","public");
    }

    if($req->filled("CategoryName")){
        $s->CategoryName = $req->CategoryName;
    }

    if($req->filled("Section")){
        $s->Section = $req->Section;
    }

    $saver = $s->save();

    if($saver){

        $message = "Category updated successfully";
        $message2 = "Updated ".$s->CategoryName." category";
        $this->audit->Auditor($req->AdminId, $message2);

        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to update a category"],400);
    }
}

function ViewCategoryFrontend(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Category::where("Section", $req->Section)->get();
    return $s;
}

function ViewCategory(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Category::get();
    return $s;
}


function ViewSingleCategory(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_A_Single_Category");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $s = Category::where("CategoryId",$req->CategoryId)->first();
    if($s==null){
        return response()->json(["message"=>"Category does not exist"],400);
    }

    $message = "Viewed a category";
    $this->audit->Auditor($req->AdminId, $message);
    return response()->json(["message"=>$s],200);

}

function DeleteCategory(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Delete_Category");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $s = Category::where("CategoryId",$req->CategoryId)->first();
    if($s==null){
        return response()->json(["message"=>"Category does not exist"],400);
    }

    $saver = $s->delete();

    if($saver){
        $message = "Deleted ".$s->CategoryName." category";
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$s->CategoryName." Deleted Successfully"],200);
    }
    else{
        return response()->json(["message"=>"Failed to delete category"],400);
    }



}

function CreateProduct(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Create_Product");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }

    $m = Menu::where("MenuId",$req->MenuId)->first();
    if($m==null){
        return response()->json(["message"=>"Menu does not exist"],400);
    }

    $c = Category::where("CategoryId",$req->CategoryId)->first();
    if($c==null){
        return response()->json(["message"=>"Category does not exist"],400);
    }




    $s = new Product();

    $s->MenuId = $m->MenuId;
    $s->CategoryId = $c->CategoryId;


    if($req->hasFile("Picture")){
        $s->Picture = $req->file("Picture")->store("","public");
    }

    if($req->filled("ProductId")){
        $s->ProductId = $req->ProductId;
    }else{
        $s->ProductId = $this->IdGenerator();
    }

    if($req->filled("Title")){
        $s->Title = $req->Title;
    }

    if($req->filled("Price")){
        $s->Price = $req->Price;
    }

    if($req->filled("Quantity")){
        $s->Quantity = $req->Quantity;
    }

    if($req->filled("Size")){
        $s->Size = $req->Size;
    }

    if($req->filled("Description")){
        $s->Description = $req->Description;
    }

    $saver = $s->save();

    if($saver){

        $p = new ProductImages();
        $p->ProductId = $s->ProductId;
        $p->Picture = $s->Picture;
        $p->save();

        $message = "Product created successfully";
        $message2 = "Created ".$s->Title." product";
        $this->audit->Auditor($req->AdminId, $message2);

        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to create product"],400);
    }

 }

function ProductImage(Request $req){

    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Create_Product");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
    }


    $s = new ProductImages();


    if($req->hasFile("Picture")){
        $s->Picture = $req->file("Picture")->store("","public");
    }

    if($req->filled("ProductId")){
        $s->ProductId = $req->ProductId;
    }

    if($req->filled("Size")){
        $s->Size = $req->Size;
    }

    $saver = $s->save();

    if($saver){

        $message = "Added Images for product with Id:".$req->ProductId;

        $this->audit->Auditor($req->AdminId, $message);

        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to add image for this product"],400);
    }


}

function ViewProductImage(Request $req){
    $s = ProductImages::where("ProductId",$req->ProductId)->get();
    if($s==null){
        return response()->json(["message"=>"Product does not exist"],400);
    }
    return $s;
}

function DeleteProductImage(Request $req){

    $this->audit->RateLimit($req->ip());
    $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Delete_Product");
    if ($rp->getStatusCode() !== 200) {
     return $rp;  // Return the authorization failure response
  }


    $s = ProductImages::where("id",$req->ProductId)->first();
    if(!$s){
        return response()->json(["message"=>"Product does not exist"],400);
    }

    $saver = $s->delete();

    if($saver){

        $message = "Deleted Images for product with Id:".$req->ProductId;

        $this->audit->Auditor($req->AdminId, $message);

        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to delete image for this product"],400);
    }

    return $s;
}





function UpdateProduct(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Update_Product");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }

    $s = Product::where("ProductId",$req->ProductId)->first();
    if($s==null){
        return response()->json(["message"=>"Product does not exist"],400);
    }


    if($req->hasFile("Picture")){
        $s->Picture = $req->file("Picture")->store("","public");
    }

    if($req->filled("Title")){
        $s->Title = $req->Title;
    }

    if($req->filled("Price")){
        $s->Price = $req->Price;
    }

    if($req->filled("Quantity")){
        $s->Quantity = $req->Quantity;
    }

    if($req->filled("Size")){
        $s->Size = $req->Size;
    }

    if($req->filled("Description")){
        $s->Description = $req->Description;
    }

    $saver = $s->save();

    if($saver){

        $message = "Product updated successfully";
        $message2 = "Updated ".$s->Title." product";
        $this->audit->Auditor($req->AdminId, $message2);

        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to update product"],400);
    }

}

public function ViewProduct(Request $req){
    $this->audit->RateLimit($req->ip());

    $currentDate = Carbon::now();

    // Fetch all products with quantity greater than 0
    $products = Product::where("Quantity", ">", 0)->get();

    // Iterate through the products and check if the ValidUntil date has passed
    foreach ($products as $product) {
        if ($product->ValidUntil && $currentDate->gt($product->ValidUntil)) {
            // If current date is greater than ValidUntil, reset discount-related fields
            $product->DiscountPrice = 0;
            $product->DiscountPercentage = 0;
            $product->ValidUntil = null;
            // Save the product
            $product->save();
        }
    }

    // Return all the products
    return $products;
}


function ViewProductAdmin(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Product::get();
    return $s;
}

function ViewCategoryProduct(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Product::where("Quantity",">",0)->where("CategoryId", $req->CategoryId)->get();
    return $s;
}




function TestRateLimit(Request $req){
    $this->audit->RateLimit($req->ip());
    return "Test is good";
}


function ViewSingleProduct(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Product::where("ProductId",$req->ProductId)->first();
    if(!$s){
        return response()->json(["message"=>"Product not found"]);
    }

    $s->ViewsCounter = $s->ViewsCounter+1;
    $s->save();

    $this->audit->ProductAssessment($req->ProductId, "Viewed Product");



    return $s;

}

function DeleteProduct(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Delete_Product");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $s = Product::where("ProductId",$req->ProductId)->first();
    if($s==null){
        return response()->json(["message"=>"Product does not exist"],400);
    }

    $saver = $s->delete();

    if($saver){
        $message = "Deleted ".$s->Title." product";
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$s->Title." Deleted Successfully"],200);
    }
    else{
        return response()->json(["message"=>"Failed to delete product"],400);
    }



}


public function SearchProducts(Request $req)
{
    $query = $req->input('query'); // Correct way to get input

    $products = Product::where('Title', 'like', "%{$query}%")
        ->orWhere('Description', 'like', "%{$query}%")
        ->orWhere('Price', 'like', "%{$query}%")
        ->get();

    return response()->json($products);
}




function IdGenerator(): string {
    $randomID = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    return $randomID;
}


}
