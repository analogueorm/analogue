<?php

namespace TestApp;

class PlainTimestamped {

	protected $table = "timestampeds";

	protected $id;

	protected $createdAt;

	protected $updatedAt;

	public function id()
	{
		return $this->id;
	}

	public function createdAt()
	{
		return $this->createdAt;
	}

	public function updatedAt()
	{
		return $this->updatedAt;
	}
}
