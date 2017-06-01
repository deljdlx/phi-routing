<?php
namespace Phi\Routing\Interfaces;




interface Route
{

	public function validate(Request $request);
	public function getHeaders();
	public function execute();

}