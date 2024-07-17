<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AuditTrialController;
use App\Models\Menu;
use App\Models\Category;
use App\Models\Product;


class MenuCategoryProduct extends Controller
{

    protected $audit;


    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }

function CreateMenu(Request $req){
    $this->audit->RateLimit($req->ip());
        $this->audit->RoleAuthenticator($req->AdminId, "Can_Create_Menu");

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

function DeleteMenu(Request $req){
    $this->audit->RateLimit($req->ip());
    $this->audit->RoleAuthenticator($req->AdminId, "Can_Delete_Menu");

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
    $this->audit->RoleAuthenticator($req->AdminId, "Can_Create_Category");
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
    $this->audit->RoleAuthenticator($req->AdminId, "Can_Update_Category");
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

function ViewCategory(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Category::get();
    return $s;
}

function ViewSingleCategory(Request $req){
    $this->audit->RateLimit($req->ip());
    $this->audit->RoleAuthenticator($req->AdminId, "Can_View_A_Single_Category");
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
    $this->audit->RoleAuthenticator($req->AdminId, "Can_Delete_Category");
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
    $this->audit->RoleAuthenticator($req->AdminId, "Can_Create_Product");

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
    $s->ProductId = $this->IdGenerator();

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

        $message = "Product created successfully";
        $message2 = "Created ".$s->Title." product";
        $this->audit->Auditor($req->AdminId, $message2);

        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to create product"],400);
    }

}

function UpdateProduct(Request $req){
    $this->audit->RateLimit($req->ip());
    $this->audit->RoleAuthenticator($req->AdminId, "Can_Update_Product");


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

function ViewProduct(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Product::where("Quantity",">",0)->get();
    return $s;
}

function TestRateLimit(Request $req){
    $this->audit->RateLimit($req->ip());
    return "Test is good";
}


function ViewSingleProduct(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = Product::where("ProductId",$req->ProductId)->first();

    $s->ViewsCounter = $s->ViewsCounter+1;
    $s->save();

    $this->audit->ProductAssessment($req->ProductId, "Viewed Product");



    return response()->json(["message"=>$s],200);

}

function DeleteProduct(Request $req){
    $this->audit->RateLimit($req->ip());
    $this->audit->RoleAuthenticator($req->AdminId, "Can_Delete_Product");
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




function IdGenerator(): string {
    $randomID = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    return $randomID;
}


}
