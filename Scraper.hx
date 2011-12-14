package ;

import haxe.Md5;
import irc.IRCSocket;
import neko.db.Connection;
import neko.db.Sqlite;
import neko.FileSystem;
import neko.io.File;
import neko.io.FileOutput;
import neko.Lib;

/**
 * ...
 * @author Andor
 */
 
enum Trigger
{
	Privmsg;
	CTCP;
	Join;
	Topic;
	Part;
	Quit;
	Raw;
	Nick;
}
 
class External 
{
	public function new ()
	{
		trigger = Trigger.Privmsg;
		name = "scraper";
		accessLevel = 0;
		
		db = Sqlite.open("scrape.db");
		db.request("CREATE TABLE IF NOT EXISTS scrape ( id INTEGER PRIMARY KEY AUTOINCREMENT, timestamp TEXT, nick TEXT, msg TEXT, url TEXT, chan TEXT, locnam TEXT, hash TEXT)");
		db.startTransaction();
		db.commit();
		if (!FileSystem.exists("./scrape_img")) { FileSystem.createDirectory("./scrape_img"); }
	}
	public var trigger : Trigger;
	public var name : String;
	public var accessLevel : Int;
	public var db : Connection;
	public function getResult(sender:IRCUser, me:IRCUser, sock:IRCSocket, message:String, target:String) : Void
	{
		
		var url : EReg = ~/http:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/;
		var img : EReg = ~/\.(jpg|png|gif|jpeg)$/;
		if (!url.match(message)) 
			return;
		if (!img.match(url.matched(0))) 
			return;
		
		Lib.println("Adding img url: " + url.matched(0));
		var localname = Std.string((db.request("SELECT MAX(id) FROM scrape").getIntResult(0) + 1)); // ID of the new entry
		var img_down = haxe.Http.requestUrl(url.matched(0)); // raw downloaded image
		var hash = Md5.encode(img_down); // md5 hash of the image
		var id : String = "new"; // ID of original stored image, if any
		var dbcheck = db.request("SELECT id, locnam FROM scrape WHERE hash = '" + hash + "'"); // DB query to check if the image is unique, if yes, should return nothing, if no, it contains the ID of the original image
		var file_ext = url.matched(0).substr(url.matched(0).lastIndexOf(".")); // File extension
		var nextres : Dynamic = null;
		if (dbcheck.length == 0) 
		{
			var fo : FileOutput = File.write("./scrape_img/" + localname + file_ext);
			fo.writeString(img_down);
			fo.flush();
			fo.close();
			Lib.println("Pic ID " + localname + " is a new, unique image, MD5 hash = " + hash);
		}
		else
		{
			nextres = dbcheck.next();
			trace("old id = " + nextres.id);
			id = nextres.id;
			Lib.println("Pic ID " + localname + " isn't unique, already downloaded. Old img id = " + id + " hash = " + hash);
		}
		trace("we're through hell");
		var values = "'" + Date.now().toString() + "'"; 
		values += ","; // timestamp done
		values += "'" + sender.nick + "'" + ","; // nick done
		values += "'" + StringTools.replace(message, "'", "''") + "'" + ","; // msg done
		values += "'" + url.matched(0) + "'" + ","; // url done
		values += "'" + target + "'" + ","; // chan done
		values += "'" + (id == "new" ? Std.string(localname) + Std.string(file_ext) : nextres.locnam) + "'" + ","; // local filename with extension  values += "'" + localname + url.matched(0).substr(url.matched(0).lastIndexOf(".")) + "'"; // no ')';
		values += "'" + hash + "'"; // hash
		
		db.request("INSERT INTO scrape (timestamp, nick, msg, url, chan, locnam, hash) VALUES (" + values + ")");
		db.commit();
	}
}