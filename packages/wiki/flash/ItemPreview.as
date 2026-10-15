package
{
   import flash.display.Loader;
   import flash.display.MovieClip;
   import flash.display.StageAlign;
   import flash.display.StageScaleMode;
   import flash.events.Event;
   import flash.events.IOErrorEvent;
   import flash.events.SecurityErrorEvent;
   import flash.external.ExternalInterface;
   import flash.geom.Rectangle;
   import flash.net.URLRequest;
   import flash.system.ApplicationDomain;
   import flash.system.LoaderContext;

   public class ItemPreview extends MovieClip
   {
      private static const FIT_RATIO:Number = 0.8;

      private var domain:ApplicationDomain = new ApplicationDomain(ApplicationDomain.currentDomain);
      private var holder:MovieClip;
      private var item:MovieClip;
      private var sLink:String = "";

      public function ItemPreview()
      {
         super();
         stage.align = StageAlign.TOP_LEFT;
         stage.scaleMode = StageScaleMode.NO_SCALE;
         holder = MovieClip(addChild(new MovieClip()));

         var params:Object = stage.loaderInfo.parameters;
         sLink = params.sLink || "";

         if (ExternalInterface.available)
         {
            ExternalInterface.addCallback("playAnim", playAnim);
         }

         var loader:Loader = new Loader();
         loader.contentLoaderInfo.addEventListener(Event.COMPLETE, onComplete, false, 0, true);
         loader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, onError, false, 0, true);
         loader.contentLoaderInfo.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onError, false, 0, true);
         loader.load(new URLRequest((params.sBase || "") + (params.sFile || "")), new LoaderContext(false, domain));
      }

      public function playAnim(anim:String) : void
      {
         if (item == null)
         {
            return;
         }

         var labels:Array = item.currentLabels;
         for (var i:int = 0; i < labels.length; i++)
         {
            if (labels[i].name == anim)
            {
               item.gotoAndPlay(anim);
               return;
            }
         }
      }

      private function onComplete(e:Event) : void
      {
         var mc:MovieClip = null;

         try
         {
            var AssetClass:Class = domain.getDefinition(sLink) as Class;
            mc = new AssetClass();
         }
         catch (err:Error)
         {
            mc = e.target.content as MovieClip;
         }

         if (mc == null)
         {
            notify("onItemPreviewFailed", "");
            return;
         }

         if (mc.scaleX == 0)
         {
            mc.scaleX = 1;
         }

         item = mc;
         holder.addChild(mc);
         fitAndCenter(mc);
         stage.addEventListener(Event.RESIZE, onResize, false, 0, true);

         var names:Array = [];
         var labels:Array = mc.currentLabels;
         for (var i:int = 0; i < labels.length; i++)
         {
            names.push(labels[i].name);
         }

         notify("onItemPreviewLoaded", names.join(","));
      }

      private function fitAndCenter(mc:MovieClip) : void
      {
         var sw:Number = stage.stageWidth;
         var sh:Number = stage.stageHeight;

         mc.scaleX = mc.scaleY = 1;
         mc.x = mc.y = 0;

         var bounds:Rectangle = mc.getBounds(mc);
         var scale:Number = Math.min(sw * FIT_RATIO / bounds.width, sh * FIT_RATIO / bounds.height);
         mc.scaleX = mc.scaleY = scale;

         var after:Rectangle = mc.getBounds(this);
         mc.x += (sw / 2) - (after.x + after.width / 2);
         mc.y += (sh / 2) - (after.y + after.height / 2);
      }

      private function onResize(e:Event) : void
      {
         if (item != null)
         {
            fitAndCenter(item);
         }
      }

      private function onError(e:Event) : void
      {
         notify("onItemPreviewFailed", "");
      }

      private function notify(callback:String, payload:String) : void
      {
         if (ExternalInterface.available)
         {
            ExternalInterface.call(callback, payload);
         }
      }
   }
}
